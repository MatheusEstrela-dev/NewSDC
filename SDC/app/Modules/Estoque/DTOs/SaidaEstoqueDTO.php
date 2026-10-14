<?php

declare(strict_types=1);

namespace App\Modules\Estoque\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Saida por requisicao (#33): os itens de um romaneio, todos do mesmo
 * almoxarifado, para um destino (municipio ou abrigo).
 */
final readonly class SaidaEstoqueDTO
{
    use SafeCastingTrait;

    /** @param list<array{produto_id: int, quantidade: int}> $itens */
    public function __construct(
        public int $almoxarifadoId,
        public string $destinoTipo,
        public ?int $destinoMunicipioId,
        public ?string $destinoDescricao,
        public array $itens,
        public ?string $documento,
        public ?string $observacao,
    ) {}

    public static function fromValidated(array $dados): self
    {
        // Mesmo produto em duas linhas vira uma linha so: a checagem de saldo
        // tem de enxergar o total pedido, nao cada linha isolada.
        $itens = [];
        foreach ($dados['itens'] as $item) {
            $id = self::asInt($item['produto_id']);
            $itens[$id] = ($itens[$id] ?? 0) + self::asInt($item['quantidade']);
        }

        return new self(
            almoxarifadoId:     self::asInt($dados['almoxarifado_id']),
            destinoTipo:        self::asString($dados['destino_tipo']),
            destinoMunicipioId: self::asIntOrNull($dados['destino_municipio_id'] ?? null),
            destinoDescricao:   self::asStringOrNull($dados['destino_descricao'] ?? null) ?: null,
            itens:              array_map(fn ($id, $qtd) => ['produto_id' => $id, 'quantidade' => $qtd], array_keys($itens), $itens),
            documento:          self::asStringOrNull($dados['documento'] ?? null) ?: null,
            observacao:         self::asStringOrNull($dados['observacao'] ?? null) ?: null,
        );
    }
}
