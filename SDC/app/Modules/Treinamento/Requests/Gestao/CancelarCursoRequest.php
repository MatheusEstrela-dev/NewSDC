<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelamento do curso pela Area do Gestor. Motivo e opcional (so vira linha
 * de historico quando preenchido - ver GestaoCursoComandos::cancelarCurso()).
 * Autorizacao fica na rota (can:treinamento.cursos.cancelar).
 */
class CancelarCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }
}
