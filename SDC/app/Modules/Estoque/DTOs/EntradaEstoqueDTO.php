<?php

declare(strict_types=1);

namespace App\Modules\Estoque\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Entrada rapida (#33, "bulk insert"): N unidades de um mesmo lote de uma vez,
 * com lote, validade, quantidade e fornecedor/doador.
 */
final readonly class EntradaEstoqueDTO
{
    use SafeCastingTrait;

    public function __construct(
        public int $produtoId,
        public int $almoxarifadoId,
        public string $lote,
        public ?string $validade,
        public int $quantidade,
        public ?string $fornecedor,
        public ?string $endereco,
        public ?string $documento,
        public ?string $observacao,
    ) {}

    public static function fromValidated(array $dados): self
    {
        return new self(
            produtoId:      self::asInt($dados['produto_id']),
            almoxarifadoId: self::asInt($dados['almoxarifado_id']),
            lote:           mb_strtoupper(trim(self::asString($dados['lote']))),
            validade:       self::asStringOrNull($dados['validade'] ?? null) ?: null,
            quantidade:     self::asInt($dados['quantidade']),
            fornecedor:     self::asStringOrNull($dados['fornecedor'] ?? null) ?: null,
            endereco:       self::asStringOrNull($dados['endereco'] ?? null) ?: null,
            documento:      self::asStringOrNull($dados['documento'] ?? null) ?: null,
            observacao:     self::asStringOrNull($dados['observacao'] ?? null) ?: null,
        );
    }
}
