<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Support\PerfilPmda;
use Illuminate\Foundation\Http\FormRequest;

class StorePmdaPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PmdaPlano::class) ?? false;
    }

    /**
     * O municipio do PMDA vem do login, nao do formulario.
     *
     * Sobrescrever aqui, e nao no controller, deixa a regra em um unico ponto e
     * preserva a validacao `exists`: um `municipio_id` forjado no POST e
     * descartado antes de chegar as rules.
     */
    protected function prepareForValidation(): void
    {
        $municipioId = PerfilPmda::deUsuario($this->user())->municipioId();

        if ($municipioId !== null) {
            $this->merge(['municipio_id' => $municipioId]);
        }
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
