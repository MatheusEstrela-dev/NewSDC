<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida dados do laudo/vistoria técnica em ocorrência RAT.
 */
class RatVistoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solicitante
            'v_solicitante_nome'             => ['required', 'string', 'max:255'],
            'v_solicitante_cpf'              => ['nullable', 'string', 'max:14'],
            'v_solicitante_telefone'         => ['nullable', 'string', 'max:20'],
            'v_solicitante_endereco'         => ['nullable', 'string', 'max:255'],
            'v_solicitante_bairro'           => ['nullable', 'string', 'max:100'],
            'v_solicitante_cep'              => ['nullable', 'string', 'max:9'],
            // Local e imóvel
            'v_local_endereco'               => ['nullable', 'string', 'max:255'],
            'v_local_complemento'            => ['nullable', 'string', 'max:255'],
            'v_tipo_area'                    => ['nullable', 'string', 'max:50'],
            'v_tipo_imovel'                  => ['nullable', 'string', 'max:50'],
            'v_tipo_construcao'              => ['nullable', 'string', 'max:50'],
            'v_tipo_edificacao'              => ['nullable', 'string', 'max:50'],
            'v_tipo_terreno_relevo'          => ['nullable', 'string', 'max:50'],
            'v_sistema_estrutural'           => ['nullable', 'string', 'max:50'],
            'v_numero_pavimentos'            => ['nullable', 'integer', 'min:0'],
            'v_estado_conservacao'           => ['nullable', 'string', Rule::in(['otimo', 'bom', 'regular', 'ruim', 'pessimo'])],
            'v_regime_ocupacao'              => ['nullable', 'string', 'max:20'],
            // Moradores
            'v_proprietario_morador'         => ['nullable', 'string', 'max:255'],
            'v_contato_telefone'             => ['nullable', 'string', 'max:20'],
            'v_numero_moradores'             => ['nullable', 'integer', 'min:0'],
            'v_ha_idosos'                    => ['nullable', 'string', 'max:10'],
            'v_ha_criancas'                  => ['nullable', 'string', 'max:10'],
            'v_ha_dificuldade_locomocao'     => ['nullable', 'string', 'max:10'],
            // Localização geográfica
            'v_endereco_imovel'              => ['nullable', 'string', 'max:255'],
            'v_bairro'                       => ['nullable', 'string', 'max:100'],
            'v_municipio'                    => ['nullable', 'integer'],
            'v_cep'                          => ['nullable', 'string', 'max:10'],
            'v_latitude'                     => ['nullable', 'numeric', 'between:-90,90'],
            'v_longitude'                    => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
