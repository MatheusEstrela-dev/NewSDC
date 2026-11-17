<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Periodo do curso inteiro (so vale quando o curso nao tem turma - a turma
 * manda no proprio periodo dela). Autorizacao fica na rota.
 */
class PeriodoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ];
    }
}
