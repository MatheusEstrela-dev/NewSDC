<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBeneficiarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaBeneficiario::class) ?? false;
    }

    /**
     * Normaliza as mascaras antes de validar. No legado isso acontecia
     * DEPOIS da validacao, com closures duplicadas no controller — o que
     * fazia 'renda' => 'max:15' validar o texto mascarado, nao o numero.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => NormalizaEntrada::cpf($this->input('cpf')),
            'agente_cpf' => NormalizaEntrada::cpf($this->input('agente_cpf')),
            'renda' => NormalizaEntrada::moeda($this->input('renda')),
            'renda_per_capita' => NormalizaEntrada::moeda($this->input('renda_per_capita')),
            'latitude' => NormalizaEntrada::decimal($this->input('latitude')),
            'longitude' => NormalizaEntrada::decimal($this->input('longitude')),
            'comprimento_telhado' => NormalizaEntrada::decimal($this->input('comprimento_telhado')),
            'largura_telhado' => NormalizaEntrada::decimal($this->input('largura_telhado')),
            'area_telhado' => NormalizaEntrada::decimal($this->input('area_telhado')),
            'comprimento_testada' => NormalizaEntrada::decimal($this->input('comprimento_testada')),
            'medida_telhado_area_fogao' => NormalizaEntrada::decimal($this->input('medida_telhado_area_fogao')),
            'testada_disp_parte_fogao' => NormalizaEntrada::decimal($this->input('testada_disp_parte_fogao')),
            'possui_deficiencia' => NormalizaEntrada::booleanoSimNao($this->input('possui_deficiencia')),
            'possui_crianca' => NormalizaEntrada::booleanoSimNao($this->input('possui_crianca')),
            'possui_idoso' => NormalizaEntrada::booleanoSimNao($this->input('possui_idoso')),
            'chefiada_mulher' => NormalizaEntrada::booleanoSimNao($this->input('chefiada_mulher')),
            'possui_fogao_lenha' => NormalizaEntrada::booleanoSimNao($this->input('possui_fogao_lenha')),
            'atendido_por_pipa' => NormalizaEntrada::booleanoSimNao($this->input('atendido_por_pipa')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Limites de idade do legado: beneficiario maior de 18, crianca
        // menor de 12, nascimento nao anterior a 1910.
        $minNascimento = CarbonImmutable::create(1910, 1, 1)->toDateString();
        $maxNascimento = CarbonImmutable::now()->subYears(18)->toDateString();
        $minNascimentoCrianca = CarbonImmutable::now()->subYears(12)->toDateString();

        return [
            // Espelha o indice unico PARCIAL do banco: registro marcado como
            // Duplicado nao bloqueia um cadastro novo com o mesmo CPF.
            // whereNot() e o unico caminho para o `<>` aqui: Rule::unique()->where()
            // aceita so dois argumentos e descartaria o operador.
            'cpf' => [
                'required',
                'string',
                'size:11',
                Rule::unique('cisterna_beneficiarios', 'cpf')
                    ->whereNull('deleted_at')
                    ->whereNot('situacao_analise', SituacaoAnalise::DUPLICADO->value),
            ],
            'nome' => ['required', 'string', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:15'],
            'data_nascimento' => ['required', 'date', "after_or_equal:{$minNascimento}", "before_or_equal:{$maxNascimento}"],
            'cadastro_unico' => ['nullable', 'string', 'max:12'],

            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'comunidade_id' => ['nullable', 'integer', 'exists:cisterna_comunidades,id'],
            'endereco' => ['nullable', 'string', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'ordem_servico_id' => ['nullable', 'integer', 'exists:cisterna_ordens_servico,id'],

            'situacao_analise' => ['nullable', Rule::in(SituacaoAnalise::valores())],
            'situacao_analise_obs' => ['nullable', 'string', 'max:255'],
            'situacao_obra' => ['nullable', Rule::in(SituacaoObra::valores())],
            'ranqueamento_ordem' => ['nullable', 'integer', 'min:1'],

            'qtd_pessoas' => ['required', 'integer', 'min:1', 'max:99'],
            'renda' => ['required', 'numeric', 'min:0'],
            'renda_per_capita' => ['nullable', 'numeric', 'min:0'],

            'possui_deficiencia' => ['required', 'boolean'],
            'comprovante_deficiencia' => ['exclude_if:possui_deficiencia,false', 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'possui_crianca' => ['required', 'boolean'],
            'data_nascimento_crianca' => ['exclude_if:possui_crianca,false', 'required', 'date', "after:{$minNascimentoCrianca}"],
            'possui_idoso' => ['required', 'boolean'],
            'chefiada_mulher' => ['required', 'boolean'],
            'comprovante_chefia_mulher' => ['exclude_if:chefiada_mulher,false', 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'comprovante_observacao' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],

            'tipo_moradia' => ['required', 'string', 'max:30'],
            'tipo_moradia_outro' => ['nullable', 'string', 'max:50'],
            'comprimento_telhado' => ['required', 'numeric', 'min:0'],
            'largura_telhado' => ['required', 'numeric', 'min:0'],
            'area_telhado' => ['nullable', 'numeric', 'min:0'],
            'comprimento_testada' => ['required', 'numeric', 'min:0'],
            'num_caidas_telhado' => ['required', 'integer', 'min:1', 'max:99'],
            'cobertura_telhado' => ['required', 'string', 'max:30'],
            'cobertura_outro' => ['nullable', 'string', 'max:150'],
            'possui_fogao_lenha' => ['required', 'boolean'],
            'medida_telhado_area_fogao' => ['nullable', 'numeric', 'min:0'],
            'testada_disp_parte_fogao' => ['nullable', 'numeric', 'min:0'],

            'atendido_por_pipa' => ['required', 'boolean'],
            'responsaveis_pipa' => ['nullable', 'array'],
            'responsaveis_pipa.*' => [Rule::in(ResponsavelPipa::valores())],
            'atendimento_pipa_outro' => ['nullable', 'string', 'max:255'],

            'agente_nome' => ['required', 'string', 'max:70'],
            'agente_cpf' => ['required', 'string', 'size:11'],
            'engenheiro_nome' => ['required', 'string', 'max:150'],
            'engenheiro_crea' => ['required', 'string', 'max:20'],

            'observacoes' => ['nullable', 'string', 'max:1000'],

            'fotos_imovel' => ['nullable', 'array', 'max:10'],
            'fotos_imovel.*.arquivo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:3072'],
            'fotos_imovel.*.angulo' => ['required', 'string', 'max:40'],
            'fotos_imovel.*.observacao' => ['nullable', 'string', 'max:262'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpf.required' => 'O CPF do beneficiario e obrigatorio.',
            'cpf.size' => 'O CPF deve ter 11 digitos.',
            'cpf.unique' => 'Este CPF ja esta cadastrado.',
            'nome.required' => 'O nome do beneficiario e obrigatorio.',
            'data_nascimento.required' => 'A data de nascimento e obrigatoria.',
            'data_nascimento.before_or_equal' => 'O beneficiario deve ser maior de 18 anos.',
            'data_nascimento.after_or_equal' => 'A data de nascimento deve ser posterior a 31 de dezembro de 1909.',
            'data_nascimento_crianca.required' => 'A data de nascimento da crianca e obrigatoria.',
            'data_nascimento_crianca.after' => 'A crianca deve ter menos de 12 anos.',
            'comprovante_deficiencia.required' => 'O anexo do laudo de deficiencia e obrigatorio.',
            'comprovante_chefia_mulher.required' => 'O comprovante para residencia chefiada por mulher e obrigatorio.',
            'latitude.required' => 'A latitude e obrigatoria.',
            'longitude.required' => 'A longitude e obrigatoria.',
            'agente_cpf.size' => 'O CPF do agente deve ter 11 digitos.',
        ];
    }
}
