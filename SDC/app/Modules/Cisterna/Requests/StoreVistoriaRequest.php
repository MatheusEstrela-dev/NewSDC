<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request unico para as tres etapas de vistoria.
 *
 * No legado eram tres formularios e tres metodos de controller
 * (storeRelatorioFinalFornecedor, storeRelatorioFinalCompdec,
 * storeRelatorioFiscalizacaoCedec), com cerca de 30 regras identicas repetidas
 * em cada um. As etapas compartilham engenheiro, data, local e checklist; so os
 * campos administrativos da CEDEC divergem. Um Request com rules() variando por
 * etapa evita a triplicacao.
 */
class StoreVistoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaVistoria::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $etapa = EtapaVistoria::tryFrom((string) $this->input('etapa'));

        $regras = [
            'beneficiario_id' => ['required', 'integer', 'exists:cisterna_beneficiarios,id'],
            'etapa' => ['required', Rule::in(EtapaVistoria::valores())],

            'engenheiro_nome' => ['required', 'string', 'max:150'],
            'engenheiro_crea' => ['required', 'string', 'max:30'],
            'data_relatorio' => ['required', 'date'],
            'local_relatorio' => ['required', 'string', 'max:255'],

            'endereco' => ['nullable', 'string', 'max:150'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'observacoes' => ['nullable', 'string', 'max:1000'],

            // Checklist. As chaves do array sao os valores do enum ItemInstalacao.
            'itens' => ['nullable', 'array'],
            'itens.*.conferido' => ['required', 'boolean'],
            'itens.*.quantidade' => ['nullable', 'numeric', 'min:0'],
            'itens.*.detalhes' => ['nullable', 'array'],
            'itens.*.detalhes.*' => ['nullable', 'string', 'max:30'],
            'itens.*.observacao' => ['nullable', 'string', 'max:255'],

            'assinatura_engenheiro' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'fotos_vistoria' => ['nullable', 'array', 'max:40'],
            'fotos_vistoria.*.arquivo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'fotos_vistoria.*.item' => ['required', Rule::in(ItemInstalacao::valores())],
            'fotos_vistoria.*.sequencia' => ['required', 'integer', 'min:1', 'max:2'],
        ];

        // O numero de instalacao e opcional: quando ausente, o
        // NumeracaoInstalacaoService pega o proximo da sequence. Nao ha teto --
        // o legado dizia impor 1800 e os dados de producao chegam a 50.000.
        if ($etapa?->alocaNumeroInstalacao() === true) {
            $regras['numero_instalacao'] = ['nullable', 'integer', 'min:1'];
        } else {
            $regras['numero_instalacao'] = ['prohibited'];
        }

        // Dados administrativos so na etapa CEDEC: obrigatorios ali, proibidos
        // nas outras. No legado nada impedia enviar processo_sei numa vistoria
        // de fornecedor, e o campo simplesmente era ignorado em silencio.
        if ($etapa?->exigeDadosAdministrativos() === true) {
            $regras['processo_sei'] = ['required', 'string', 'max:100'];
            $regras['contrato'] = ['required', 'string', 'max:100'];
            $regras['empenho'] = ['required', 'string', 'max:100'];
            $regras['placa_obras'] = ['required', 'integer', 'min:0'];
            $regras['engenheiro_art'] = ['required', 'string', 'max:50'];
        } else {
            foreach (['processo_sei', 'contrato', 'empenho', 'placa_obras', 'engenheiro_art'] as $campo) {
                $regras[$campo] = ['prohibited'];
            }
        }

        return $regras;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'etapa.required' => 'Informe a etapa da vistoria.',
            'engenheiro_nome.required' => 'O nome do engenheiro responsavel e obrigatorio.',
            'engenheiro_crea.required' => 'O CREA do engenheiro e obrigatorio.',
            'processo_sei.required' => 'O processo SEI e obrigatorio na fiscalizacao da CEDEC.',
            'engenheiro_art.required' => 'A ART e obrigatoria na fiscalizacao da CEDEC.',
            'numero_instalacao.prohibited' => 'Somente a vistoria do fornecedor recebe numero de instalacao.',
            'processo_sei.prohibited' => 'Dados administrativos pertencem apenas a etapa da CEDEC.',
        ];
    }
}
