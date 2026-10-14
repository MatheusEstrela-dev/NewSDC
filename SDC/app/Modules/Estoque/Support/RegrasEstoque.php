<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Support;

use App\Modules\Estoque\Enums\CategoriaEstoque;
use Illuminate\Validation\Rule;

/**
 * Regras de validacao do estoque, compartilhadas pela tela (Inertia) e pela
 * API v1: as duas portas aceitam exatamente o mesmo contrato.
 */
final class RegrasEstoque
{
    public static function produto(?int $ignorarId = null, bool $parcial = false): array
    {
        $obrigatorio = $parcial ? 'sometimes' : 'required';

        return [
            'sku'               => [$obrigatorio, 'string', 'max:40', Rule::unique('estoque_produtos', 'sku')->ignore($ignorarId)],
            'nome'              => [$obrigatorio, 'string', 'max:150'],
            'categoria'         => [$obrigatorio, Rule::enum(CategoriaEstoque::class)],
            'unidade_base'      => ['sometimes', 'string', 'max:10'],
            'patrimonio'        => ['sometimes', 'nullable', 'string', 'max:40', Rule::unique('estoque_produtos', 'patrimonio')->ignore($ignorarId)],
            'estoque_minimo'    => ['sometimes', 'integer', 'min:0'],
            'controla_validade' => ['sometimes', 'boolean'],
            'ativo'             => ['sometimes', 'boolean'],
        ];
    }

    public static function entrada(): array
    {
        return [
            'produto_id'      => ['required', 'integer', Rule::exists('estoque_produtos', 'id')->whereNull('deleted_at')],
            'almoxarifado_id' => ['required', 'integer', Rule::exists('estoque_almoxarifados', 'id')->where('ativo', true)],
            'lote'            => ['required', 'string', 'max:40'],
            'validade'        => ['nullable', 'date'],
            'quantidade'      => ['required', 'integer', 'min:1', 'max:1000000'],
            'fornecedor'      => ['nullable', 'string', 'max:150'],
            'endereco'        => ['nullable', 'string', 'max:40'],
            'documento'       => ['nullable', 'string', 'max:60'],
            'observacao'      => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function saida(): array
    {
        return [
            'almoxarifado_id'      => ['required', 'integer', Rule::exists('estoque_almoxarifados', 'id')],
            'destino_tipo'         => ['required', Rule::in(['municipio', 'abrigo'])],
            'destino_municipio_id' => ['required_if:destino_tipo,municipio', 'nullable', 'integer', Rule::exists('municipios', 'id')],
            'destino_descricao'    => ['required_if:destino_tipo,abrigo', 'nullable', 'string', 'max:150'],
            'itens'                => ['required', 'array', 'min:1', 'max:100'],
            'itens.*.produto_id'   => ['required', 'integer', Rule::exists('estoque_produtos', 'id')],
            'itens.*.quantidade'   => ['required', 'integer', 'min:1'],
            'documento'            => ['nullable', 'string', 'max:60'],
            'observacao'           => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function almoxarifado(): array
    {
        return [
            'nome'         => ['required', 'string', 'max:120'],
            'tipo'         => ['required', Rule::in(['central', 'regional', 'movel'])],
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')],
            'capacidade'   => ['nullable', 'integer', 'min:1'],
        ];
    }

    public static function kit(): array
    {
        return [
            'codigo'              => ['required', 'string', 'max:40', Rule::unique('estoque_kits', 'codigo')],
            'nome'                => ['required', 'string', 'max:150'],
            'descricao'           => ['nullable', 'string', 'max:2000'],
            'itens'               => ['required', 'array', 'min:1'],
            'itens.*.produto_id'  => ['required', 'integer', 'distinct', Rule::exists('estoque_produtos', 'id')->whereNull('deleted_at')],
            'itens.*.quantidade'  => ['required', 'integer', 'min:1'],
        ];
    }
}
