<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cadastro e edicao do material do catalogo (RN-07).
 *
 * Um Request unico para as duas operacoes porque as regras sao as mesmas: o
 * que muda e o registro ignorado na checagem de nome duplicado.
 */
class SalvarMaterialAhRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A rota ja exige humanitaria.materiais.manage; repetir aqui
        // devolveria 403 duas vezes pelo mesmo motivo.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                // Nome duplicado confunde na hora de escolher o material do
                // pedido: a lista mostra so o nome.
                Rule::unique('materiais_ah', 'nome')->ignore($id),
            ],
            'descricao'              => ['nullable', 'string', 'max:2000'],
            'unidade_medida'         => ['required', 'string', 'max:30'],
            'disponivel_para_pedido' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome'                   => 'nome',
            'descricao'              => 'descrição',
            'unidade_medida'         => 'unidade de medida',
            'disponivel_para_pedido' => 'disponibilidade para pedido',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe um material com esse nome.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            // Sem uppercase: o catalogo migrado tem "UN", "Metro" e "Unitario",
            // e normalizar aqui alteraria registro que o usuario nao pediu para
            // mudar. A tela oferece as unidades ja usadas como sugestao.
            'unidade_medida' => trim((string) $this->input('unidade_medida')),
            'descricao'      => trim((string) $this->input('descricao')) ?: null,
        ]);
    }
}
