<?php

declare(strict_types=1);

namespace App\Http\Requests\Rat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida payloads de histórico/log manual de uma ocorrência RAT.
 */
class RatHistoricoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('rat.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'historico'             => ['required', 'array', 'min:1'],
            'historico.*.tipo'      => ['required', 'string', 'max:50'],
            'historico.*.titulo'    => ['required', 'string', 'max:255'],
            'historico.*.descricao' => ['nullable', 'string', 'max:1000'],
            'historico.*.data'      => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'historico.required'          => 'O histórico não pode estar vazio.',
            'historico.*.tipo.required'   => 'O tipo de evento é obrigatório.',
            'historico.*.titulo.required' => 'O título do evento é obrigatório.',
        ];
    }
}
