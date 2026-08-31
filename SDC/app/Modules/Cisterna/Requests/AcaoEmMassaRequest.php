<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Legado: updateEstadoMass, CisternaController.php:1473, que aceitava
 * qualquer string em `acao` e respondia 400 quando nao reconhecia.
 */
class AcaoEmMassaRequest extends FormRequest
{
    public const ACOES = [
        'alocar_em_ordem_servico',
        'remover_de_ordem_servico',
        'alterar_situacao_obra',
    ];

    public function authorize(): bool
    {
        // updateEmMassa, e nao 'update': a policy de update exige a INSTANCIA
        // para fazer o recorte territorial, e aqui nao existe instancia unica.
        // O recorte por municipio e aplicado no service, ao whereIn dos ids.
        return $this->user()?->can('updateEmMassa', CisternaBeneficiario::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'acao' => ['required', Rule::in(self::ACOES)],
            'ids' => ['required', 'array', 'min:1', 'max:5000'],
            'ids.*' => ['integer', 'exists:cisterna_beneficiarios,id'],

            'ordem_servico_id' => [
                Rule::requiredIf(fn (): bool => $this->input('acao') === 'alocar_em_ordem_servico'),
                'nullable',
                'integer',
                'exists:cisterna_ordens_servico,id',
            ],
            'situacao_obra' => [
                Rule::requiredIf(fn (): bool => $this->input('acao') === 'alterar_situacao_obra'),
                'nullable',
                Rule::in(SituacaoObra::valores()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'acao.in' => 'Acao em massa nao reconhecida.',
            'ids.required' => 'Selecione ao menos um beneficiario.',
            'ordem_servico_id.required' => 'Selecione a ordem de servico de destino.',
            'situacao_obra.required' => 'Selecione a nova situacao da obra.',
        ];
    }
}
