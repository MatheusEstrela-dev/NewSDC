<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Gestao;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoEventoCronograma;
use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Models\CursoCronograma;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Turma;
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
     * @param  array{nome: string, data_inicio: string|\DateTimeInterface, data_fim: string|\DateTimeInterface, numero_vagas: int, local?: string|null, municipio_id?: int|null}  $dados
     */
    public function criarTurma(Treinamento $curso, array $dados, User $autor): Turma
    {
        if (! $curso->usaTurma()) {
            throw new DomainException('Curso EAD nao trabalha com turmas: o aluno entra a qualquer momento enquanto o curso esta publicado.');
        }

        $inicio = CarbonImmutable::parse($dados['data_inicio'])->startOfDay();
        $fim = CarbonImmutable::parse($dados['data_fim'])->startOfDay();
        $vagas = (int) $dados['numero_vagas'];

        $this->exigirPeriodoValido($inicio, $fim);
        $this->exigirVagasValidas($vagas);

        $turma = Turma::create([
            'treinamento_id' => $curso->id,
            'nome' => trim((string) $dados['nome']),
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'numero_vagas' => $vagas,
            'local' => $dados['local'] ?? null,
            'municipio_id' => $dados['municipio_id'] ?? null,
        ]);

        $this->historico->registrar(
            (int) $curso->id,
            TipoEventoCurso::TURMA_CRIADA,
            'Turma "'.$turma->nome.'" criada.',
            [
                'nome' => $turma->nome,
                'data_inicio' => $inicio->toDateString(),
                'data_fim' => $fim->toDateString(),
                'numero_vagas' => $vagas,
            ],
            (int) $autor->getKey(),
            (int) $turma->getKey(),
        );

        return $turma;
    }

    /**
     * Reduzir vagas abaixo do que ja esta ocupado e recusado - e a leitura e
     * feita com a linha travada, senao duas edicoes simultaneas poderiam passar
     * cada uma pela checagem da outra.
     *
     * @param  array{nome?: string, data_inicio?: string|\DateTimeInterface, data_fim?: string|\DateTimeInterface, numero_vagas?: int, local?: string|null, municipio_id?: int|null}  $dados
     */
    public function atualizarTurma(Turma $turma, array $dados, User $autor): Turma
    {
        $curso = $turma->treinamento;

        if ($curso === null) {
            throw new DomainException('Treinamento da turma nao encontrado.');
        }

        $antes = [
            'nome' => $turma->nome,
            'data_inicio' => $turma->data_inicio->toDateString(),
            'data_fim' => $turma->data_fim->toDateString(),
            'numero_vagas' => $turma->numero_vagas,
        ];

        $inicio = isset($dados['data_inicio'])
            ? CarbonImmutable::parse($dados['data_inicio'])->startOfDay()
            : CarbonImmutable::parse($turma->data_inicio)->startOfDay();
        $fim = isset($dados['data_fim'])
            ? CarbonImmutable::parse($dados['data_fim'])->startOfDay()
            : CarbonImmutable::parse($turma->data_fim)->startOfDay();
        $vagas = isset($dados['numero_vagas']) ? (int) $dados['numero_vagas'] : (int) $turma->numero_vagas;

        $this->exigirPeriodoValido($inicio, $fim);
        $this->exigirVagasValidas($vagas);

        ConexaoTreinamento::transacao(function () use ($curso, $turma, $dados, $inicio, $fim, $vagas) {
            $ocupadas = ControleDeVagas::contagemComTrava($curso, $turma)['ocupadas'];

            if ($vagas < $ocupadas) {
                throw new DomainException('A turma ja tem '.$ocupadas.' inscricao(oes) aprovada(s): as vagas nao podem ficar abaixo disso.');
            }

            $turma->update([
                'nome' => isset($dados['nome']) ? trim((string) $dados['nome']) : $turma->nome,
                'data_inicio' => $inicio,
                'data_fim' => $fim,
                'numero_vagas' => $vagas,
                'local' => array_key_exists('local', $dados) ? $dados['local'] : $turma->local,
                'municipio_id' => array_key_exists('municipio_id', $dados) ? $dados['municipio_id'] : $turma->municipio_id,
            ]);
        });

        $turma->refresh();

        $this->historico->registrar(
            (int) $turma->treinamento_id,
            TipoEventoCurso::TURMA_ATUALIZADA,
            'Turma "'.$turma->nome.'" atualizada.',
            ['antes' => $antes, 'depois' => [
                'nome' => $turma->nome,
                'data_inicio' => $turma->data_inicio->toDateString(),
                'data_fim' => $turma->data_fim->toDateString(),
                'numero_vagas' => $turma->numero_vagas,
            ]],
            (int) $autor->getKey(),
            (int) $turma->getKey(),
        );

        return $turma;
    }

    /**
     * Soft delete. Turma com inscricao viva nao sai: a FK e ON DELETE RESTRICT
     * e, mais que isso, sumir com a turma deixaria a inscricao sem periodo e
     * sem vaga de referencia.
     */
    public function removerTurma(Turma $turma, User $autor): void
    {
        $vivas = Inscricao::query()
            ->porTurma((int) $turma->getKey())
            ->whereIn('status', [StatusInscricao::PENDENTE->value, StatusInscricao::APROVADA->value])
            ->count();

        if ($vivas > 0) {
            throw new DomainException('Esta turma tem '.$vivas.' inscricao(oes) ativa(s) e nao pode ser removida.');
        }

        $treinamentoId = (int) $turma->treinamento_id;
        $nome = (string) $turma->nome;

        $turma->delete();

        $this->historico->registrar(
            $treinamentoId,
            TipoEventoCurso::TURMA_REMOVIDA,
            'Turma "'.$nome.'" removida.',
            ['nome' => $nome],
            (int) $autor->getKey(),
        );
    }

    /**
     * Vagas do curso inteiro (null = ilimitado). Em curso com turma este
     * numero e um teto global: o limite que recusa a inscricao e o da turma.
     */
    public function definirVagasDoCurso(Treinamento $curso, ?int $vagas, User $autor): void
    {
        if ($vagas !== null) {
            $this->exigirVagasValidas($vagas);
        }

        $antes = $curso->numero_vagas;

        ConexaoTreinamento::transacao(function () use ($curso, $vagas) {
            $ocupadas = ControleDeVagas::contagemComTrava($curso, null)['ocupadas'];

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

    /** Datas do curso inteiro. Em curso com turma, quem manda e a turma. */
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
     * @param  array{titulo: string, inicio: string|\DateTimeInterface, fim?: string|\DateTimeInterface|null, tipo?: string|TipoEventoCronograma, descricao?: string|null, local?: string|null, turma_id?: int|null}  $dados
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
            $item->turma_id === null ? null : (int) $item->turma_id,
        );

        return $item;
    }

    /**
     * @param  array{titulo?: string, inicio?: string|\DateTimeInterface, fim?: string|\DateTimeInterface|null, tipo?: string|TipoEventoCronograma, descricao?: string|null, local?: string|null, turma_id?: int|null}  $dados
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
            'turma_id' => $item->turma_id,
        ];

        $item->update($this->dadosDoCronograma($curso, array_merge($base, $dados)));
        $item->refresh();

        $this->historico->registrar(
            (int) $item->treinamento_id,
            TipoEventoCurso::CRONOGRAMA_ATUALIZADO,
            'Item "'.$item->titulo.'" do cronograma atualizado.',
            ['titulo' => $item->titulo, 'inicio' => $item->inicio->toDateTimeString(), 'tipo' => $item->tipo->value],
            (int) $autor->getKey(),
            $item->turma_id === null ? null : (int) $item->turma_id,
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

        $turmaId = empty($dados['turma_id']) ? null : (int) $dados['turma_id'];

        if ($turmaId !== null && ! Turma::query()->porTreinamento((int) $curso->id)->whereKey($turmaId)->exists()) {
            throw new DomainException('A turma escolhida nao pertence a este treinamento.');
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
            'turma_id' => $turmaId,
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
