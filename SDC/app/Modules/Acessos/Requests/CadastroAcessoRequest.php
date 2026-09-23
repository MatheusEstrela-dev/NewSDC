<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CadastroAcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post')
            ? 'acessos.cadastros.create'
            : 'acessos.cadastros.edit');
    }

    public function rules(): array
    {
        $id = $this->route('cadastro')?->id;
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'in:masp,militar,outros'],
            'documento' => ['required', 'string', 'max:30'],
            'cpf' => ['required', 'digits:11', Rule::unique('acessos_cadastros', 'cpf')->ignore($id)],
            'login_ad' => ['nullable', 'string', 'max:100', Rule::unique('acessos_cadastros', 'login_ad')->ignore($id)],
            'email_corporativo' => ['nullable', 'email', 'max:255'],
            'email_pessoal' => ['nullable', 'email', 'max:255'],
            'telefone_mesa' => ['nullable', 'string', 'max:30'],
            'telefone_whatsapp' => ['nullable', 'string', 'max:30'],
            'setor' => ['nullable', 'string', 'max:150'],
            'posto' => ['nullable', 'string', 'max:150'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'observacoes_ti' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
