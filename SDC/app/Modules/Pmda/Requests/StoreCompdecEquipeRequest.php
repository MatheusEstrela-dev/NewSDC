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

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            // Sem isto sai "The selected funcao is invalid", que nao diz o que
            // enviar. Pela tela o select ja manda certo; quem erra e integracao.
            'funcao.in' => 'A função deve ser uma destas: coordenador, agente, tecnico, apoio ou outro (em minúsculas).',
        ];
    }
}
