<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Presentation\Http\Requests;

use App\Modules\AjudaHumanitaria\Domain\ValueObjects\StatusBeneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\TipoCadastroBeneficiario;
use App\Modules\AjudaHumanitaria\Domain\ValueObjects\SituacaoVulnerabilidade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request: Update Beneficiário
 *
 * Validação para atualização de beneficiário
 */
class BeneficiarioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: Implementar policy
    }

    public function rules(): array
    {
        $beneficiarioId = $this->route('id');

        return [
            // Dados do responsável
            'nome_responsavel' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14', Rule::unique('beneficiarios', 'cpf')->ignore($beneficiarioId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],

            // Tipo e Status
            'tipo_cadastro' => ['required', Rule::in(TipoCadastroBeneficiario::values())],
            'status' => ['nullable', Rule::in(StatusBeneficiario::values())],

            // Endereço
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'endereco_rua' => ['nullable', 'string', 'max:255'],
            'endereco_numero' => ['nullable', 'string', 'max:10'],
            'endereco_complemento' => ['nullable', 'string', 'max:100'],
            'endereco_bairro' => ['nullable', 'string', 'max:100'],
            'endereco_cep' => ['nullable', 'string', 'max:9'],

            // Situação
            'situacoes_vulnerabilidade' => ['nullable', 'array'],
            'situacoes_vulnerabilidade.*' => [Rule::in(SituacaoVulnerabilidade::values())],
            'observacoes' => ['nullable', 'string'],

            // Abrigo
            'abrigo_atual_id' => ['nullable', 'integer', 'exists:abrigos,id'],

            // Membros da família
            'membros' => ['nullable', 'array'],
            'membros.*.nome_completo' => ['required', 'string', 'max:255'],
            'membros.*.data_nascimento' => ['nullable', 'date'],
            'membros.*.cpf' => ['nullable', 'string', 'max:14'],
            'membros.*.parentesco' => ['required', 'string', 'max:50'],
            'membros.*.observacoes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome_responsavel.required' => 'O nome do responsável é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'municipio_id.required' => 'O município é obrigatório.',
            'municipio_id.exists' => 'Município inválido.',
            'tipo_cadastro.required' => 'O tipo de cadastro é obrigatório.',
            'membros.*.nome_completo.required' => 'O nome do membro da família é obrigatório.',
        ];
    }
}
