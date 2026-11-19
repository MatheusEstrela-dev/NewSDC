<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Emissao do certificado de instrutor por um administrador: para um professor
 * designado (user_id) ou, no presencial, para o nome do instrutor.
 */
class EmitirCertificadoInstrutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.certificados.download') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer'],
            'nome' => ['required_without:user_id', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required_without' => 'Informe o nome do instrutor.',
        ];
    }
}
