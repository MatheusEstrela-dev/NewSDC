<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Gestao;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoEventoCronograma;
use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Models\CursoCronograma;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\HistoricoDoCursoService;
use App\Modules\Treinamento\Services\TreinamentoService;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\ControleDeVagas;
use Carbon\CarbonImmutable;
use DomainException;

/**
 * Lado de ESCRITA do fluxo do gestor (CQRS so aqui, nao no modulo inteiro).
 *
 * Toda operacao: valida a regra, grava e registra no historico do curso. Nao
 * reimplementa o que ja existe - cancelar passa por TreinamentoService, que e
 * quem conhece as transicoes de status.
 *
 * Sem estado: resolvido pelo container e seguro como singleton sob Octane.
 */
class GestaoCursoComandos
{
    public function __construct(
        private readonly HistoricoDoCursoService $historico,
        private readonly TreinamentoService $treinamentos,
    ) {}

    /**
     * Vagas do curso (null = ilimitado).
     */
    public function definirVagasDoCurso(Treinamento $curso, ?int $vagas, User $autor): void
    {
        if ($vagas !== null) {
            $this->exigirVagasValidas($vagas);
        }

        $antes = $curso->numero_vagas;

        ConexaoTreinamento::transacao(function () use ($curso, $vagas) {
            $ocupadas = ControleDeVagas::contagemComTrava($curso)['ocupadas'];

            if ($vagas !== null && $vagas < $ocupadas) {
                throw new DomainException('O curso ja tem '.$ocupadas.' inscricao(oes) aprovada(s): as vagas nao podem ficar abaixo disso.');
            }

            $curso->update(['numero_vagas' => $vagas]);
        });

        $this->historico->registrar(
            (int) $curso->id,
            TipoEventoCurso::VAGAS_ALTERADAS,
            'Vagas do curso alteradas.',
            ['antes' => $antes, 'depois' => $vagas],
            (int) $autor->getKey(),
        );
    }

    /** Datas do curso. */
    public function definirPeriodoDoCurso(Treinamento $curso, string|\DateTimeInterface|null $inicio, string|\DateTimeInterface|null $fim, User $autor): void
    {
        $novoInicio = $inicio === null ? null : CarbonImmutable::parse($inicio)->startOfDay();
        $novoFim = $fim === null ? null : CarbonImmutable::parse($fim)->startOfDay();

        if ($novoInicio !== null && $novoFim !== null) {
            $this->exigirPeriodoValido($novoInicio, $novoFim);
        }

        $antes = [
            'data_inicio' => $curso->data_inicio?->toDateString(),
            'data_fim' => $curso->data_fim?->toDateString(),
        ];

        $curso->update([
            'data_inicio' => $novoInicio,
            'data_fim' => $novoFim,
        ]);

        $this->historico->registrar(
            (int) $curso->id,
            TipoEventoCurso::PERIODO_ALTERADO,
            'Periodo do curso alterado.',
            ['antes' => $antes, 'depois' => [
                'data_inicio' => $novoInicio?->toDateString(),
                'data_fim' => $novoFim?->toDateString(),
            ]],
            (int) $autor->getKey(),
        );
    }

    /**
     * @param  array{titulo: string, inicio: string|\DateTimeInterface, fim?: string|\DateTimeInterface|null, tipo?: string|TipoEventoCronograma, descricao?: string|null, local?: string|null}  $dados
     */
    public function adicionarItemDeCronograma(Treinamento $curso, array $dados, User $autor): CursoCronograma
    {
        $item = CursoCronograma::create($this->dadosDoCronograma($curso, $dados));

        $this->historico->registrar(
            (int) $curso->id,
            TipoEventoCurso::CRONOGRAMA_ADICIONADO,
            'Item "'.$item->titulo.'" adicionado ao cronograma.',
            ['titulo' => $item->titulo, 'inicio' => $item->inicio->toDateTimeString(), 'tipo' => $item->tipo->value],
            (int) $autor->getKey(),
        );

        return $item;
    }

    /**
     * @param  array{titulo?: string, inicio?: string|\DateTimeInterface, fim?: string|\DateTimeInterface|null, tipo?: string|TipoEventoCronograma, descricao?: string|null, local?: string|null}  $dados
     */
    public function atualizarItemDeCronograma(CursoCronograma $item, array $dados, User $autor): CursoCronograma
    {
        $curso = $item->treinamento;

        if ($curso === null) {
            throw new DomainException('Treinamento do item de cronograma nao encontrado.');
        }

        $base = [
            'titulo' => $item->titulo,
            'inicio' => $item->inicio,
            'fim' => $item->fim,
            'tipo' => $item->tipo,
            'descricao' => $item->descricao,
            'local' => $item->local,
        ];

        $item->update($this->dadosDoCronograma($curso, array_merge($base, $dados)));
        $item->refresh();

        $this->historico->registrar(
            (int) $item->treinamento_id,
            TipoEventoCurso::CRONOGRAMA_ATUALIZADO,
            'Item "'.$item->titulo.'" do cronograma atualizado.',
            ['titulo' => $item->titulo, 'inicio' => $item->inicio->toDateTimeString(), 'tipo' => $item->tipo->value],
            (int) $autor->getKey(),
        );

        return $item;
    }

    public function removerItemDeCronograma(CursoCronograma $item, User $autor): void
    {
        $treinamentoId = (int) $item->treinamento_id;
        $titulo = (string) $item->titulo;

        $item->delete();

        $this->historico->registrar(
            $treinamentoId,
            TipoEventoCurso::CRONOGRAMA_REMOVIDO,
            'Item "'.$titulo.'" removido do cronograma.',
            ['titulo' => $titulo],
            (int) $autor->getKey(),
        );
    }

    /**
     * Cancela o curso. A transicao de status continua sendo do
     * TreinamentoService (que conhece as transicoes validas); aqui so se
     * acrescenta o registro no historico.
     */
    public function cancelarCurso(Treinamento $curso, User $autor, ?string $motivo = null): void
    {
        // transicionarStatus ja grava o CURSO_CANCELADO no historico: aqui so
        // entra a linha do motivo, quando houver, para nao duplicar o evento.
        $this->treinamentos->transicionarStatus($curso, StatusTreinamento::CANCELADO);

        if ($motivo !== null && trim($motivo) !== '') {
            $this->historico->registrar(
                (int) $curso->id,
                TipoEventoCurso::CURSO_CANCELADO,
                'Motivo do cancelamento: '.trim($motivo),
                ['motivo' => trim($motivo)],
                (int) $autor->getKey(),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function dadosDoCronograma(Treinamento $curso, array $dados): array
    {
        $inicio = CarbonImmutable::parse($dados['inicio']);
        $fim = empty($dados['fim']) ? null : CarbonImmutable::parse($dados['fim']);

        if ($fim !== null && $fim->lessThan($inicio)) {
            throw new DomainException('O fim do item do cronograma nao pode ser antes do inicio.');
        }

        $tipo = $dados['tipo'] ?? TipoEventoCronograma::AULA;
        $tipo = $tipo instanceof TipoEventoCronograma
            ? $tipo
            : (TipoEventoCronograma::tryFrom((string) $tipo) ?? throw new DomainException('Tipo de item do cronograma invalido.'));

        $titulo = trim((string) $dados['titulo']);

        if ($titulo === '') {
            throw new DomainException('O item do cronograma precisa de um titulo.');
        }

        return [
            'treinamento_id' => $curso->id,
            'titulo' => $titulo,
            'descricao' => $dados['descricao'] ?? null,
            'tipo' => $tipo->value,
            'inicio' => $inicio,
            'fim' => $fim,
            'local' => $dados['local'] ?? null,
        ];
    }

    private function exigirPeriodoValido(CarbonImmutable $inicio, CarbonImmutable $fim): void
    {
        if ($fim->lessThan($inicio)) {
            throw new DomainException('A data de termino nao pode ser antes da data de inicio.');
        }
    }

    private function exigirVagasValidas(int $vagas): void
    {
        if ($vagas < 1) {
            throw new DomainException('O numero de vagas precisa ser maior que zero.');
        }
    }
}
