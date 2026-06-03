<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidarCronoViagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.viagens.validar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'aprovada' => ['required', 'boolean'],
            'obs_aprovacao' => ['nullable', 'string', 'max:1000', 'required_if:aprovada,false'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'obs_aprovacao.required_if' => 'Ao rejeitar a viagem, informe o motivo.',
        ];
    }
}
