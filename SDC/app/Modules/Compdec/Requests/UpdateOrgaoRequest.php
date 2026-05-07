<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Requests;

use App\Modules\Compdec\Enums\StatusOrgao;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateOrgaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $orgao = $this->route('orgao');

        return $this->user()?->can('update', $orgao) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgaoId = $this->route('orgao')?->id ?? null;

        return [
            'codigo' => [
                'nullable', 'string', 'max:50',
                Rule::unique('compdec_orgaos', 'codigo')->ignore($orgaoId),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', new Enum(TipoOrgao::class)],
            'status' => ['required', new Enum(StatusOrgao::class)],
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')],
            'orgao_superior_id' => ['nullable', 'integer', Rule::exists('compdec_orgaos', 'id')],

            'email' => ['nullable', 'email', 'max:255'],
            'email_secundario' => ['nullable', 'email', 'max:255'],
            'email_terciario' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'telefone_secundario' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string'],

            'responsavel_nome' => ['nullable', 'string', 'max:255'],
            'responsavel_cpf' => ['nullable', 'string', 'max:14'],
            'responsavel_telefone' => ['nullable', 'string', 'max:20'],
            'responsavel_email' => ['nullable', 'email', 'max:255'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'lei_criacao_numero' => ['nullable', 'string', 'max:50'],
            'lei_criacao_data' => ['nullable', 'date'],
            'decreto_numero' => ['nullable', 'string', 'max:50'],
            'decreto_data' => ['nullable', 'date'],
            'portaria_numero' => ['nullable', 'string', 'max:50'],
            'portaria_data' => ['nullable', 'date'],

            'qtd_efetivo' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'qtd_nupdec' => ['nullable', 'integer', 'min:0', 'max:32767'],

            'tem_sede_propria' => ['nullable', 'boolean'],
            'tem_viatura' => ['nullable', 'boolean'],
            'tem_mapeamento_risco' => ['nullable', 'boolean'],
            'tem_simulado' => ['nullable', 'boolean'],
            'tem_cartao_pdc' => ['nullable', 'boolean'],

            'abrangencia' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'capacidades' => ['nullable', 'array'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v): void {
            $tipo = $this->input('tipo');
            $superiorId = $this->input('orgao_superior_id');
            $orgaoId = $this->route('orgao')?->id;

            if ($orgaoId && (int) $superiorId === (int) $orgaoId) {
                $v->errors()->add('orgao_superior_id', 'Um orgao nao pode ser superior de si mesmo.');
                return;
            }

            if (! $superiorId) {
                return;
            }

            $superior = Orgao::find($superiorId);
            if (! $superior) {
                $v->errors()->add('orgao_superior_id', 'Orgao superior nao encontrado.');
                return;
            }

            if ($tipo === TipoOrgao::REDEC->value && $superior->tipo !== TipoOrgao::CEDEC) {
                $v->errors()->add('orgao_superior_id', 'REDEC deve ter um CEDEC como orgao superior.');
            }
            if ($tipo === TipoOrgao::COMPDEC->value && $superior->tipo !== TipoOrgao::REDEC) {
                $v->errors()->add('orgao_superior_id', 'COMPDEC deve ter um REDEC como orgao superior.');
            }
            if ($tipo === TipoOrgao::CEDEC->value) {
                $v->errors()->add('orgao_superior_id', 'CEDEC nao deve ter orgao superior.');
            }
        });
    }
}
