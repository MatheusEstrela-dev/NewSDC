<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ficha cadastral do COMPDEC editada de dentro da aba COMPDEC do PMDA.
 * Grava no registro mestre (Orgao tipo COMPDEC) do municipio do plano.
 */
class UpdateCompdecFichaRequest extends FormRequest
{
    private const BOOLS = [
        'tem_sede_propria', 'tem_viatura', 'tem_mapeamento_risco', 'tem_simulado', 'tem_cartao_pdc',
        'tem_computador', 'tem_curso_gestao', 'tem_curso_sco', 'tem_workshop_pdc', 'tem_experiencia',
        'possui_capacitacao_pdc', 'possui_compdec', 'possui_nupdec', 'possui_efetivo',
        'nao_possui_lei', 'nao_possui_decreto', 'nao_possui_portaria',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(fn ($k) => $this->boolean($k), array_combine(self::BOOLS, self::BOOLS)));
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['ativo', 'inativo', 'em_implantacao', 'suspenso'])],
            // Atos legais
            'lei_criacao_numero' => ['nullable', 'string', 'max:50'],
            'lei_criacao_data'   => ['nullable', 'date'],
            'decreto_numero'     => ['nullable', 'string', 'max:50'],
            'decreto_data'       => ['nullable', 'date'],
            'portaria_numero'    => ['nullable', 'string', 'max:50'],
            'portaria_data'      => ['nullable', 'date'],
            // Contato
            'email'               => ['nullable', 'email', 'max:255'],
            'email_secundario'    => ['nullable', 'email', 'max:255'],
            'email_terciario'     => ['nullable', 'email', 'max:255'],
            'telefone'            => ['nullable', 'string', 'max:20'],
            'telefone_secundario' => ['nullable', 'string', 'max:20'],
            'fax'                 => ['nullable', 'string', 'max:20'],
            'endereco'            => ['nullable', 'string', 'max:1000'],
            // Quantitativos
            'qtd_efetivo' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'qtd_nupdec'  => ['nullable', 'integer', 'min:0', 'max:32767'],
            // Capacidades / capacitacao (metadata)
            'tempo_experiencia_anos' => ['nullable', 'integer', 'min:0', 'max:99'],
            'capacitacao_nupdec'     => ['nullable', 'string', 'max:255'],
            'data_curso_gestao'      => ['nullable', 'date'],
            'data_curso_sco'         => ['nullable', 'date'],
            'data_workshop_pdc'      => ['nullable', 'date'],
            'data_capacitacao_pdc'   => ['nullable', 'date'],
            'associacao'             => ['nullable', 'string', 'max:150'],
            // Booleans
            'tem_sede_propria'     => ['boolean'],
            'tem_viatura'          => ['boolean'],
            'tem_mapeamento_risco' => ['boolean'],
            'tem_simulado'         => ['boolean'],
            'tem_cartao_pdc'       => ['boolean'],
            'tem_computador'       => ['boolean'],
            'tem_curso_gestao'     => ['boolean'],
            'tem_curso_sco'        => ['boolean'],
            'tem_workshop_pdc'     => ['boolean'],
            'tem_experiencia'      => ['boolean'],
            'possui_capacitacao_pdc' => ['boolean'],
            'possui_compdec'       => ['boolean'],
            'possui_nupdec'        => ['boolean'],
            'possui_efetivo'       => ['boolean'],
            'nao_possui_lei'       => ['boolean'],
            'nao_possui_decreto'   => ['boolean'],
            'nao_possui_portaria'  => ['boolean'],
        ];
    }
}
