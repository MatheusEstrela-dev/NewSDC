<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\SituacaoAnalise;
use Illuminate\Validation\Rule;

/**
 * Herda as regras e a normalizacao do Store, ajustando o que muda na edicao:
 *  - o unique de CPF ignora o proprio registro
 *  - o comprovante volta a ser opcional quando ja existe arquivo salvo
 *    (comportamento do legado, CisternaController.php:1287-1297)
 */
class UpdateBeneficiarioRequest extends StoreBeneficiarioRequest
{
    public function authorize(): bool
    {
        $beneficiario = $this->route('beneficiario');

        return $beneficiario !== null && ($this->user()?->can('update', $beneficiario) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $regras = parent::rules();
        $beneficiario = $this->route('beneficiario');

        // Continua espelhando o indice unico PARCIAL do banco, agora tambem
        // ignorando o proprio registro em edicao.
        $regras['cpf'] = [
            'required',
            'string',
            'size:11',
            Rule::unique('cisterna_beneficiarios', 'cpf')
                ->ignore($beneficiario?->getKey())
                ->whereNull('deleted_at')
                ->whereNot('situacao_analise', SituacaoAnalise::DUPLICADO->value),
        ];

        // Se marcou 'sim' e ja tem arquivo salvo, o envio e opcional: e uma
        // substituicao, nao uma exigencia nova.
        if ($beneficiario?->getMedia('comprovantes')->firstWhere('custom_properties.tipo', 'deficiencia') !== null) {
            $regras['comprovante_deficiencia'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'];
        }

        if ($beneficiario?->getMedia('comprovantes')->firstWhere('custom_properties.tipo', 'chefia_mulher') !== null) {
            $regras['comprovante_chefia_mulher'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'];
        }

        return $regras;
    }
}
