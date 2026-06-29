<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePmdaPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'motivo'       => ['nullable', 'string'],
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
        ];
    }
}
