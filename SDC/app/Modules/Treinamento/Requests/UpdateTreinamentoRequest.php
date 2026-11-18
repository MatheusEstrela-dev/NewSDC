<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ProfessorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateTreinamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(ProfessorService::PERMISSAO_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'carga_horaria' => ['sometimes', 'required', 'integer', 'min:1'],
            'categoria' => ['sometimes', 'required', 'string', 'in:EVENTO,CURSO'],
            // Enum da modalidade, nao lista literal: SEMI_PRESENCIAL (bloco 1)
            // ficava de fora da validacao mesmo o banco ja aceitando.
            'tipo' => ['sometimes', 'required', 'string', new Enum(TipoTreinamento::class)],
            'publico_alvo' => ['sometimes', 'required', 'string', 'in:SERVIDORES,CIDADAOS'],
            'inscricao_aprovacao_automatica' => ['boolean'],
            'instrutor' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'numero_vagas' => ['nullable', 'integer', 'min:1'],
            'percentual_frequencia_minimo' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'presenca_autoconfirmavel' => ['nullable', 'boolean'],
            // Municipio de realizacao (alimenta o painel de indicadores). A tabela
            // municipios fica no banco sdc (conexao padrao), nao no do treinamento.
            'curso_id' => $this->regraDoCurso(),
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')->where('uf', 'MG')],
        ];
    }

    /**
     * Turma antiga (sem curso) aceita null ate ser vinculada; turma que ja tem
     * curso exige um. O curso atual continua valido mesmo se foi inativado.
     */
    private function regraDoCurso(): array
    {
        $atual = Treinamento::query()->whereKey((int) $this->route('id'))->value('curso_id');

        return [
            $atual === null ? 'nullable' : 'required',
            'integer',
            Rule::exists(config('database.treinamento_connection').'.treinamento.cursos', 'id')
                ->where(fn ($q) => $q->whereNull('deleted_at')
                    ->where(fn ($w) => $w->where('ativo', true)->orWhere('id', $atual ?? 0))),
        ];
    }
}
