<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Requests;

use App\Modules\Compdec\Enums\FuncaoEquipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateEquipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $equipe = $this->route('equipe');

        return $this->user()?->can('update', $equipe) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'funcao' => ['required', new Enum(FuncaoEquipe::class)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'ativo' => ['nullable', 'boolean'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
