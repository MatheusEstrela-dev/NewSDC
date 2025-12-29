<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VincularUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('compdec.manage');
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'funcao' => ['required', Rule::in(['coordenador', 'agente', 'tecnico', 'apoio'])],
            'is_principal' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'O usuário é obrigatório',
            'user_id.exists' => 'Usuário não encontrado',
            'funcao.required' => 'A função é obrigatória',
            'funcao.in' => 'Função inválida',
        ];
    }
}
