<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LiberarInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.inscricoes.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string', 'size:11'],
            'curso_id' => ['required', 'integer', Rule::exists((string) config('database.treinamento_connection').'.treinamento.cursos', 'id')->whereNull('deleted_at')],
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.size' => 'O CPF deve conter 11 numeros.',
            'motivo.min' => 'Descreva o motivo da liberacao (minimo de 10 caracteres).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf'))]);
    }
}
