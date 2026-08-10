<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recebimento de material em um deposito.
 *
 * Quantidade so positiva. As 22 linhas de quantidade negativa da carga sao
 * correcao manual de saldo que o legado lancava como entrada, e repetir esse
 * atalho aqui esconderia uma baixa dentro de um recebimento. Baixa tem de ter
 * tipo proprio no ledger.
 */
class StoreEntradaAhRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A rota ja exige humanitaria.estoque.movimentar.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'deposito_id'      => ['required', 'integer', Rule::exists('ajuda_h_depositos', 'id')],
            'fonte_recurso_id' => ['nullable', 'integer', Rule::exists('ajuda_h_fontes_recurso', 'id')],
            'fornecedor_id'    => ['nullable', 'integer', Rule::exists('ajuda_h_fornecedores', 'id')],
            'nota_fiscal'      => ['nullable', 'string', 'max:70'],
            // Retroativo e aceito porque a nota chega depois do material, e o
            // ledger tem de contar quando ele entrou no deposito. Futuro nao:
            // seria saldo que ainda nao existe.
            'recebido_em'      => ['required', 'date', 'before_or_equal:today'],
            'observacao'       => ['nullable', 'string', 'max:2000'],

            'itens'                        => ['required', 'array', 'min:1'],
            'itens.*.material_ah_id'       => ['required', 'integer', Rule::exists('materiais_ah', 'id')],
            'itens.*.qtd'                  => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'itens.*.valor_unitario'       => ['nullable', 'numeric', 'gte:0', 'max:99999999'],
            'itens.*.data_validade'        => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'deposito_id'            => 'depósito',
            'fonte_recurso_id'       => 'fonte de recurso',
            'fornecedor_id'          => 'fornecedor',
            'nota_fiscal'            => 'nota fiscal',
            'recebido_em'            => 'data de recebimento',
            'itens'                  => 'itens',
            'itens.*.material_ah_id' => 'material',
            'itens.*.qtd'            => 'quantidade',
            'itens.*.valor_unitario' => 'valor unitário',
            'itens.*.data_validade'  => 'validade',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required'              => 'Informe ao menos um material recebido.',
            'itens.min'                   => 'Informe ao menos um material recebido.',
            'itens.*.qtd.gt'              => 'A quantidade recebida deve ser maior que zero.',
            'recebido_em.before_or_equal' => 'A data de recebimento não pode ser futura.',

            // O projeto nao tem lang/pt_BR/validation.php, entao regra sem
            // mensagem propria chega ao usuario como a chave crua
            // ("validation.exists"). Ate existir o arquivo, as do formulario
            // ficam escritas aqui.
            'deposito_id.exists'                => 'Depósito não encontrado.',
            'fonte_recurso_id.exists'           => 'Fonte de recurso não encontrada.',
            'fornecedor_id.exists'              => 'Fornecedor não encontrado.',
            'itens.*.material_ah_id.exists'     => 'Material não encontrado no catálogo.',
            'deposito_id.required'              => 'Escolha o depósito que recebeu o material.',
            'recebido_em.required'              => 'Informe a data de recebimento.',
            'itens.*.material_ah_id.required'   => 'Escolha o material do item.',
            'itens.*.qtd.required'              => 'Informe a quantidade do item.',
        ];
    }

    /**
     * O mesmo material duas vezes na mesma entrada geraria dois lancamentos no
     * ledger para o mesmo par, o que fecha o saldo certo mas deixa o extrato
     * confuso. Somar cabe ao usuario, que sabe se sao lotes distintos.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $materiais = array_column((array) $this->input('itens', []), 'material_ah_id');
            $repetidos = array_diff_assoc($materiais, array_unique($materiais));

            if ($repetidos !== []) {
                $validator->errors()->add('itens', 'O mesmo material aparece mais de uma vez. Some as quantidades em um único item.');
            }
        });
    }
}
