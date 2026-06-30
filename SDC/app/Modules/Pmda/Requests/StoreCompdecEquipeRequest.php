<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Membro da equipe COMPDEC editado a partir da aba do PMDA.
 * Persiste como CompdecEquipe do orgao COMPDEC do municipio do plano.
 */
class StoreCompdecEquipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo', true)]);
    }

    public function rules(): array
    {
        return [
            'nome'     => ['required', 'string', 'max:110'],
            'funcao'   => ['required', Rule::in(['coordenador', 'agente', 'tecnico', 'apoio', 'outro'])],
            'cpf'      => ['nullable', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular'  => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:150'],
            'ativo'    => ['boolean'],
        ];
    }
}
