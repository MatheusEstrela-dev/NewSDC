<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Enums\CategoriaTreinamento;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Rules\CursoSemOutroEad;
use App\Modules\Treinamento\Support\ClassificacaoDeOferta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreTreinamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.cursos.create') ?? false;
    }

    /** "Com tutoria" so vale para EAD/online: nos demais e gravado como false. */
    protected function prepareForValidation(): void
    {
        $admite = ClassificacaoDeOferta::admiteTutoria(
            CategoriaTreinamento::tryFrom((string) $this->input('categoria')),
            TipoTreinamento::tryFrom((string) $this->input('tipo')),
        );

        if (! $admite) {
            $this->merge(['com_tutoria' => false]);
        }
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'carga_horaria' => ['required', 'integer', 'min:1'],
            'categoria' => ['required', 'string', 'in:EVENTO,CURSO'],
            // Enum da modalidade, nao lista literal: SEMI_PRESENCIAL (bloco 1)
            // ficava de fora da validacao mesmo o banco ja aceitando.
            'tipo' => ['required', 'string', new Enum(TipoTreinamento::class)],
            'publico_alvo' => ['required', 'string', 'in:SERVIDORES,CIDADAOS'],
            'inscricao_aprovacao_automatica' => ['boolean'],
            'instrutor' => ['nullable', 'string', 'max:255'],
            'local' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'numero_vagas' => ['nullable', 'integer', 'min:1'],
            'percentual_frequencia_minimo' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'presenca_autoconfirmavel' => ['nullable', 'boolean'],
            // Turmas com periodo e tutor definidos (categoria "Cursos com tutoria").
            'com_tutoria' => ['boolean'],
            // Municipio de realizacao (alimenta o painel de indicadores). A tabela
            // municipios fica no banco sdc (conexao padrao), nao no do treinamento.
            // Curso do catalogo (banco do treinamento): obrigatorio e ativo.
            'curso_id' => array_filter([
                'required',
                'integer',
                Rule::exists(config('database.treinamento_connection').'.treinamento.cursos', 'id')
                    ->where(fn ($q) => $q->where('ativo', true)->whereNull('deleted_at')),
                $this->input('tipo') === TipoTreinamento::EAD->value ? new CursoSemOutroEad : null,
            ]),
            'municipio_id' => ['nullable', 'integer', Rule::exists('municipios', 'id')->where('uf', 'MG')],
        ];
    }
}
