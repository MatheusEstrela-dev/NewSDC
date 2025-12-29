<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrgaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('compdec.manage');
    }

    public function rules(): array
    {
        return [
            'codigo' => 'nullable|string|max:50|unique:orgaos,codigo',
            'nome' => 'required|string|max:255',
            'tipo' => ['required', Rule::in(['compdec', 'redec', 'cedec'])],
            'municipio_id' => 'nullable|exists:municipios,id',
            'orgao_superior_id' => 'nullable|exists:orgaos,id',
            'status' => ['required', Rule::in(['ativo', 'inativo', 'em_implantacao', 'suspenso'])],
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string',
            'responsavel_nome' => 'nullable|string|max:255',
            'responsavel_cpf' => 'nullable|string|max:14',
            'responsavel_telefone' => 'nullable|string|max:20',
            'responsavel_email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'abrangencia' => 'nullable|array',
            'abrangencia.*' => 'exists:municipios,id',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.unique' => 'Este código já está em uso',
            'nome.required' => 'O nome é obrigatório',
            'tipo.required' => 'O tipo é obrigatório',
            'tipo.in' => 'Tipo inválido',
            'status.required' => 'O status é obrigatório',
            'status.in' => 'Status inválido',
            'municipio_id.exists' => 'Município não encontrado',
            'orgao_superior_id.exists' => 'Órgão superior não encontrado',
            'email.email' => 'Email inválido',
            'responsavel_email.email' => 'Email do responsável inválido',
        ];
    }
}
