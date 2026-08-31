<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Support\NormalizaEntrada;

final readonly class ItemConferidoDTO
{
    /**
     * @param  array<string, string>|null  $detalhes
     */
    public function __construct(
        public ItemInstalacao $item,
        public bool $conferido,
        public ?float $quantidade = null,
        public ?array $detalhes = null,
        public ?string $observacao = null,
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(string $item, array $d): self
    {
        $enum = ItemInstalacao::from($item);

        // Detalhes so fazem sentido em fixacao. Em qualquer outro item o
        // valor e descartado, em vez de sujar o jsonb.
        $detalhes = null;
        if ($enum->aceitaDetalhes() && ! empty($d['detalhes']) && is_array($d['detalhes'])) {
            $detalhes = array_map(fn ($v): string => (string) $v, $d['detalhes']);
        }

        return new self(
            item: $enum,
            conferido: NormalizaEntrada::booleanoSimNao($d['conferido'] ?? null) ?? false,
            quantidade: NormalizaEntrada::decimal($d['quantidade'] ?? null),
            detalhes: $detalhes,
            observacao: $d['observacao'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // A unidade nao vem do formulario: e propriedade do item. Calha e
        // tubulacao em metros, pecas de PVC em unidades, o resto sem
        // quantidade. No legado a mesma peca aparecia como calha_metros numa
        // tabela e qtd_calha noutra.
        $unidade = $this->quantidade === null ? null : $this->item->unidadePadrao();

        return [
            'item' => $this->item->value,
            'conferido' => $this->conferido,
            'quantidade' => $this->quantidade,
            'unidade' => $unidade?->value,
            'detalhes' => $this->detalhes,
            'observacao' => $this->observacao,
        ];
    }
}
