<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criacao de turma pela Area do Gestor.
 *
 * Autorizacao fica na rota (can:treinamento.cursos.configurar), nao aqui -
 * authorize() so devolve true. A regra de dominio (curso EAD nao usa turma,
 * periodo e vagas validos) e do GestaoCursoComandos::criarTurma().
 */
class TurmaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'numero_vagas' => ['required', 'integer', 'min:1'],
            'local' => ['nullable', 'string', 'max:255'],
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')->where('uf', 'MG')],
        ];
    }
}
