<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use App\Modules\Inventario\DTOs\RemanejamentoData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * So estrutura e existencia. Repeticao, situacao do equipamento, emprestimo e
 * estacao ocupada sao regras de dominio e ficam em RemanejamentoService, com
 * mensagem por item.
 */
class SalvarRemanejamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->isMethod('post')
            ? 'inventario.remanejamentos.create'
            : 'inventario.remanejamentos.edit');
    }

    public function rules(): array
    {
        return [
            'observacao' => ['nullable', 'string', 'max:5000'],
            'pessoas' => ['required', 'array', 'min:1', 'max:100'],
            'pessoas.*.usuario_id' => ['required', 'integer', 'exists:users,id'],
            'pessoas.*.estacao_origem_id' => ['nullable', 'integer', 'exists:inventario_ti_estacoes,id'],
            'pessoas.*.estacao_destino_id' => ['nullable', 'integer', 'exists:inventario_ti_estacoes,id'],
            'pessoas.*.condicao_destino' => ['nullable', 'string', 'max:60'],
            'pessoas.*.equipamento_ids' => ['required', 'array', 'min:1', 'max:50'],
            // O distinct sob dois curingas compara o lote inteiro, nao so o bloco.
            'pessoas.*.equipamento_ids.*' => ['integer', 'distinct', Rule::exists('inventario_ti_equipamentos', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'pessoas.required' => 'Informe ao menos uma pessoa no remanejamento.',
            'pessoas.*.usuario_id.required' => 'Selecione a pessoa.',
            'pessoas.*.equipamento_ids.required' => 'Selecione ao menos um equipamento.',
            'pessoas.*.equipamento_ids.min' => 'Selecione ao menos um equipamento.',
            'pessoas.*.equipamento_ids.max' => 'No máximo :max equipamentos por pessoa.',
            'pessoas.*.equipamento_ids.*.distinct' => 'Equipamento repetido no lote: cada equipamento entra uma vez só.',
            'pessoas.*.equipamento_ids.*.exists' => 'Equipamento não encontrado.',
        ];
    }

    public function dados(): RemanejamentoData
    {
        return RemanejamentoData::fromArray($this->validated(), (int) $this->user()->id);
    }
}
