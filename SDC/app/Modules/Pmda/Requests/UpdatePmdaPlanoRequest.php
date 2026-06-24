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
            'cobra_iss'        => ['nullable', 'boolean'],
            'num_lei_iss'      => ['nullable', 'string', 'max:30'],
            'aliquota_iss'     => ['nullable', 'numeric', 'between:0,99.99'],
            'resp_cob_iss'     => ['nullable', 'string', 'max:30'],
        ];
    }
}
