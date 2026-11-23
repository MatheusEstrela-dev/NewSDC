<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use App\Modules\Treinamento\Enums\SituacaoParticipacao;
use App\Modules\Treinamento\Enums\TipoTreinamento;

/**
 * Filtros do painel de indicadores das Capacitacoes. Imutavel: uma instancia
 * por requisicao, montada em fromArray() e repassada inteira ao service.
 *
 * Valor invalido vira null (filtro ignorado) em vez de 422: o painel e so
 * leitura e um link velho com parametro quebrado deve abrir mesmo assim.
 *
 * situacoes vem sempre normalizada: sem repeticao, na ordem do enum, e as tres
 * quando o parametro falta ou nao traz nenhum valor valido (sem filtro).
 */
final readonly class IndicadoresFiltroDTO
{
    private const ANO_MINIMO = 2000;

    private const ANO_MAXIMO = 2100;

    /** @var list<string> */
    public array $situacoes;

    /**
     * @param  list<string>  $situacoes  valores de SituacaoParticipacao; vazio = todas
     */
    public function __construct(
        public ?int $ano = null,
        public ?int $redecId = null,
        public ?int $municipioId = null,
        public ?int $treinamentoId = null,
        public ?string $tipo = null,
        public ?int $cursoId = null,
        array $situacoes = [],
    ) {
        $this->situacoes = self::normalizarSituacoes($situacoes);
    }

    /**
     * @param  array<string, mixed>  $query  query string (o DTO nao conhece o Request HTTP)
     */
    public static function fromArray(array $query): self
    {
        $ano = self::inteiroPositivo($query['ano'] ?? null);
        $situacoes = $query['situacoes'] ?? [];

        return new self(
            ano: $ano !== null && $ano >= self::ANO_MINIMO && $ano <= self::ANO_MAXIMO ? $ano : null,
            redecId: self::inteiroPositivo($query['redec_id'] ?? null),
            municipioId: self::inteiroPositivo($query['municipio_id'] ?? null),
            treinamentoId: self::inteiroPositivo($query['treinamento_id'] ?? null),
            tipo: TipoTreinamento::tryFrom((string) ($query['tipo'] ?? ''))?->value,
            cursoId: self::inteiroPositivo($query['curso_id'] ?? null),
            situacoes: is_array($situacoes) ? array_values($situacoes) : [$situacoes],
        );
    }

    /** Mesma selecao sem o ano: a serie historica por ano precisa de todos os anos. */
    public function semAno(): self
    {
        return new self(null, $this->redecId, $this->municipioId, $this->treinamentoId, $this->tipo, $this->cursoId, $this->situacoes);
    }

    /**
     * Mesma selecao so com os concluidos: a satisfacao so existe para quem
     * concluiu no prazo, seja qual for o filtro de situacoes da tela.
     */
    public function soConcluidos(): self
    {
        return new self($this->ano, $this->redecId, $this->municipioId, $this->treinamentoId, $this->tipo, $this->cursoId, [SituacaoParticipacao::CONCLUIDO->value]);
    }

    /** Ha recorte por situacao (menos que as tres marcadas)? */
    public function filtraSituacao(): bool
    {
        return count($this->situacoes) < count(SituacaoParticipacao::cases());
    }

    /** Ha recorte de curso ou de turma? A satisfacao detalha por turma quando sim. */
    public function filtraCursoOuTurma(): bool
    {
        return $this->cursoId !== null || $this->treinamentoId !== null;
    }

    /** @return array{ano: ?int, tipo: ?string, redec_id: ?int, municipio_id: ?int, curso_id: ?int, treinamento_id: ?int, situacoes: list<string>} */
    public function toArray(): array
    {
        return [
            'ano' => $this->ano,
            'tipo' => $this->tipo,
            'redec_id' => $this->redecId,
            'municipio_id' => $this->municipioId,
            'curso_id' => $this->cursoId,
            'treinamento_id' => $this->treinamentoId,
            'situacoes' => $this->situacoes,
        ];
    }

    private static function inteiroPositivo(mixed $valor): ?int
    {
        if (! is_scalar($valor) || ! preg_match('/^\d+$/', (string) $valor)) {
            return null;
        }

        $inteiro = (int) $valor;

        return $inteiro > 0 ? $inteiro : null;
    }

    /**
     * @param  array<mixed>  $valores
     * @return list<string>
     */
    private static function normalizarSituacoes(array $valores): array
    {
        $validos = array_map(
            fn ($v) => is_scalar($v) ? SituacaoParticipacao::tryFrom((string) $v) : null,
            $valores
        );

        $marcadas = array_values(array_filter(
            SituacaoParticipacao::cases(),
            fn (SituacaoParticipacao $s) => in_array($s, $validos, true)
        ));

        return array_map(
            fn (SituacaoParticipacao $s) => $s->value,
            $marcadas !== [] ? $marcadas : SituacaoParticipacao::cases()
        );
    }
}
