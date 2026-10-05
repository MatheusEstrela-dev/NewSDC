<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmitirCcpaeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:100', Rule::unique('pae_ccpae', 'codigo')],
            'dt_emissao' => ['required', 'date', 'before_or_equal:today'],
            'empreendimento_novo' => ['required', 'boolean'],
            'dt_licenca_operacao' => [
                Rule::requiredIf(fn (): bool => $this->boolean('empreendimento_novo')),
                'nullable',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ja existe um CCPAE com este codigo.',
            'dt_licenca_operacao.required' => 'Empreendimento novo: informe a data da Licenca de Operacao (Art. 4).',
        ];
    }
}
