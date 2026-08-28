<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * So o plantonista da vaga e alteravel.
 *
 * Data e tipo de turno definem a IDENTIDADE da vaga -- ha indice unico parcial
 * (data, tipo_turno_id) sobre eles. Mudar qualquer um dos dois e, na pratica,
 * apagar uma vaga e criar outra, e e assim que a tela faz.
 */
class UpdateEscalaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plantao.escala.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'plantonista_id' => [
                'required',
                'integer',
                Rule::exists('plantao_plantonistas', 'user_id')->where('ativo', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plantonista_id.exists' => 'Selecione um plantonista ativo.',
        ];
    }
}
