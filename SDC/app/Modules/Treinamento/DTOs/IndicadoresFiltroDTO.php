<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use App\Modules\Treinamento\Enums\TipoTreinamento;

/**
 * Filtros do painel de indicadores das Capacitacoes. Imutavel: uma instancia
 * por requisicao, montada em fromArray() e repassada inteira ao service.
 *
 * Valor invalido vira null (filtro ignorado) em vez de 422: o painel e so
 * leitura e um link velho com parametro quebrado deve abrir mesmo assim.
 */
final readonly class IndicadoresFiltroDTO
{
    private const ANO_MINIMO = 2000;

    private const ANO_MAXIMO = 2100;

    public function __construct(
        public ?int $ano = null,
        public ?int $redecId = null,
        public ?int $municipioId = null,
        public ?int $treinamentoId = null,
        public ?string $tipo = null,
    ) {}

    /**
     * @param  array<string, mixed>  $query  query string (o DTO nao conhece o Request HTTP)
     */
    public static function fromArray(array $query): self
    {
        $ano = self::inteiroPositivo($query['ano'] ?? null);

        return new self(
            ano: $ano !== null && $ano >= self::ANO_MINIMO && $ano <= self::ANO_MAXIMO ? $ano : null,
            redecId: self::inteiroPositivo($query['redec_id'] ?? null),
            municipioId: self::inteiroPositivo($query['municipio_id'] ?? null),
            treinamentoId: self::inteiroPositivo($query['treinamento_id'] ?? null),
            tipo: TipoTreinamento::tryFrom((string) ($query['tipo'] ?? ''))?->value,
        );
    }

    /** Mesma selecao sem o ano: a serie historica por ano precisa de todos os anos. */
    public function semAno(): self
    {
        return new self(null, $this->redecId, $this->municipioId, $this->treinamentoId, $this->tipo);
    }

    /** @return array{ano: ?int, redec_id: ?int, municipio_id: ?int, treinamento_id: ?int, tipo: ?string} */
    public function toArray(): array
    {
        return [
            'ano' => $this->ano,
            'redec_id' => $this->redecId,
            'municipio_id' => $this->municipioId,
            'treinamento_id' => $this->treinamentoId,
            'tipo' => $this->tipo,
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
}
