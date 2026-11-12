<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Prova;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Criacao e edicao de questao. "Exatamente uma correta" e regra do servico
 * (ProvaConteudoService), que e quem protege o gabarito.
 */
class SalvarQuestaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enunciado' => ['required', 'string', 'max:5000'],
            'alternativas' => ['required', 'array', 'min:2', 'max:10'],
            'alternativas.*.id' => ['sometimes', 'nullable', 'integer'],
            'alternativas.*.texto' => ['required', 'string', 'max:2000'],
            'alternativas.*.correta' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'alternativas.min' => 'Informe ao menos duas alternativas.',
            'alternativas.max' => 'Use no maximo dez alternativas.',
        ];
    }
}
