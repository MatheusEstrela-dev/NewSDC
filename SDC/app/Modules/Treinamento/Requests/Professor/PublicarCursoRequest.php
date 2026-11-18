<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class PublicarCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** "O que mudou" so e exigido a partir da versao 2. */
    public function rules(): array
    {
        $novaVersao = (int) $this->route('treinamento')?->versao > 1;

        return [
            'versao_notas' => [$novaVersao ? 'required' : 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'versao_notas.required' => 'Informe o que mudou nesta versao.',
            'versao_notas.max' => 'O texto do que mudou aceita no maximo 1000 caracteres.',
        ];
    }
}
