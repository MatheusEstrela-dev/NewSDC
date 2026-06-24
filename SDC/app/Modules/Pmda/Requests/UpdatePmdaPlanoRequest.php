<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePmdaPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'acoes'            => ['nullable', 'string'],
            'qtd_caminhao'     => ['nullable', 'integer', 'min:0'],
            'pop_at_municipio' => ['nullable', 'integer', 'min:0'],
            // ISS
            'cobra_iss'        => ['nullable', 'boolean'],
            'num_lei_iss'      => ['nullable', 'string', 'max:30'],
            'aliquota_iss'     => ['nullable', 'numeric', 'between:0,99.99'],
            'resp_cob_iss'     => ['nullable', 'string', 'max:30'],
            // Municipio / Prefeitura
            'nome_prefeito'    => ['nullable', 'string', 'max:110'],
            'tel_prefeitura'   => ['nullable', 'string', 'max:20'],
            'tel_prefeito'     => ['nullable', 'string', 'max:20'],
            'cel_prefeito'     => ['nullable', 'string', 'max:20'],
            'endereco'         => ['nullable', 'string', 'max:150'],
            'bairro'           => ['nullable', 'string', 'max:60'],
            'cep'              => ['nullable', 'string', 'max:10'],
            'email_prefeitura' => ['nullable', 'email', 'max:110'],
            'populacao'        => ['nullable', 'integer', 'min:0'],
            'pop_rural'        => ['nullable', 'integer', 'min:0'],
            'area'             => ['nullable', 'numeric', 'min:0'],
            // COMPDEC
            'compdec_coordenador' => ['nullable', 'string', 'max:110'],
            'compdec_decreto'     => ['nullable', 'string', 'max:50'],
            'compdec_lei'         => ['nullable', 'string', 'max:50'],
            'compdec_tel'         => ['nullable', 'string', 'max:20'],
            'compdec_email'       => ['nullable', 'email', 'max:110'],
        ];
    }
}
