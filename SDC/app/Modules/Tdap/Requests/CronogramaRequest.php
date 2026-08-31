<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\PontoCaptacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

/*
|--------------------------------------------------------------------------
| Requests centralizados do dominio Cronograma (TDAP)
|--------------------------------------------------------------------------
| Arquivo unico mapeado via classmap (composer.json). As classes estao
| organizadas por segmento. IMPORTANTE: cada classe abstrata vem ANTES de
| suas subclasses, pois o PHP declara as classes deste arquivo na ordem em
| que aparecem; uma subclasse declarada antes da pai causa fatal error.
*/

/*
|--------------------------------------------------------------------------
| Segmento: Cronograma
|--------------------------------------------------------------------------
*/

abstract class AbstractCronogramaRequest extends FormRequest
{
    abstract protected function numeroUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        if ($this->input('numero')) {
            $this->merge(['numero' => mb_strtoupper(trim((string) $this->input('numero')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero'                => ['required', 'string', 'max:20', $this->numeroUniqueRule()],
            'empenho'               => ['nullable', 'string', 'max:30'],
            'ata_id'                => ['required', 'integer', Rule::exists('tdap_atas', 'id')->whereNull('deleted_at')],
            'lote_id'               => ['required', 'integer', Rule::exists('tdap_lotes', 'id')->whereNull('deleted_at')],
            'municipio_id'          => ['required', 'integer', Rule::exists('municipios', 'id')],
            'prestador_id'          => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'cnpj'                  => ['nullable', 'string', 'max:18'],
            'consumo_diario'        => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'dias'                  => ['required', 'integer', 'min:1', 'max:1000'],
            'usar_fator_manual'     => ['nullable', 'boolean'],
            'fator'                 => ['nullable', 'numeric', 'min:0.01', 'max:9999999.99', 'required_if:usar_fator_manual,1,true'],
            'dt_inicio'             => ['required', 'date'],
            'dt_final'              => ['required', 'date', 'after_or_equal:dt_inicio'],
            'dt_inicio_prorrogacao' => ['nullable', 'date', 'after_or_equal:dt_final'],
            'dt_final_prorrogacao'  => ['nullable', 'date', 'after_or_equal:dt_inicio_prorrogacao', 'required_with:dt_inicio_prorrogacao'],
            'justificativa'         => ['nullable', 'string', 'max:5000'],
            'nota_empenho'          => ['nullable', 'string', 'max:50'],
            'ponto_captacao_id'     => ['required', 'integer', Rule::exists('pip_pmda_ponto', 'id')->whereNull('deleted_at')],
            'observacao'            => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Coerencia referencial: Lote -> Ata/Municipio/Prestador.
     * Garante que o lote escolhido pertence ao mesmo contexto declarado.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $loteId = (int) $this->input('lote_id');
            if ($loteId === 0) {
                return;
            }

            $lote = Lote::find($loteId);
            if (! $lote) {
                return;
            }

            if ((int) $lote->ata_id !== (int) $this->input('ata_id')) {
                $v->errors()->add('lote_id', 'O Lote selecionado nao pertence a Ata escolhida.');
            }
            // O lote atende varios municipios: basta o escolhido estar entre
            // eles (antes comparava com o municipio_id unico do lote).
            $municipioId = (int) $this->input('municipio_id');
            if ($municipioId > 0 && ! $lote->municipios()->whereKey($municipioId)->exists()) {
                $v->errors()->add('municipio_id', 'O Municipio nao pertence ao Lote escolhido.');
            }
            if ((int) $lote->prestador_id !== (int) $this->input('prestador_id')) {
                $v->errors()->add('prestador_id', 'O Prestador nao confere com o Lote escolhido.');
            }

            $pontoId = (int) $this->input('ponto_captacao_id');
            if ($pontoId > 0) {
                $ponto = PontoCaptacao::find($pontoId);
                if ($ponto && (int) $ponto->municipio_id !== (int) $this->input('municipio_id')) {
                    $v->errors()->add('ponto_captacao_id', 'O Ponto de Captacao nao pertence ao Municipio do Lote.');
                }
            }
        });
    }
}

class StoreCronogramaRequest extends AbstractCronogramaRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.create') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_cronogramas', 'numero')->whereNull('deleted_at');
    }
}

class UpdateCronogramaRequest extends AbstractCronogramaRequest
{
    // `route('cronograma')` e o Model apos SubstituteBindings; o `(int)` de
    // antes lancava Error e o PUT respondia 500 sem nem validar.
    use \App\Modules\Tdap\Requests\Concerns\ResolveIdDaRota;

    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_cronogramas', 'numero')
            ->ignore($this->idDaRota('cronograma'))
            ->whereNull('deleted_at');
    }
}

/*
|--------------------------------------------------------------------------
| Segmento: CronoCaminhao (alocacao de caminhao no cronograma)
|--------------------------------------------------------------------------
*/

/**
 * Base SOLID para alocacao de Caminhao em Cronograma.
 *
 * Diferenca contextual entre Store e Update:
 *   - Store: usuario informa cronograma_id + caminhao_id (alocacao nova).
 *   - Update: cronograma_id e caminhao_id NAO podem mudar (sao a chave da
 *             alocacao); apenas comunidade_id/agua_prevista/num_viagens/ordem
 *             sao editaveis. Subclasses controlam via includeVinculo().
 */
abstract class AbstractCronoCaminhaoRequest extends FormRequest
{
    /**
     * Inclui validacao de cronograma_id + caminhao_id no payload?
     * Store: true (requer ambos).
     * Update: false (chave da alocacao e imutavel).
     */
    abstract protected function includeVinculo(): bool;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'comunidade_id' => ['nullable', 'integer'],
            'agua_prevista' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'num_viagens'   => ['required', 'integer', 'min:1', 'max:10000'],
            'ordem'         => ['nullable', 'integer', 'min:0', 'max:255'],
        ];

        if ($this->includeVinculo()) {
            $rules['cronograma_id'] = ['required', 'integer', Rule::exists('tdap_cronogramas', 'id')->whereNull('deleted_at')];
            $rules['caminhao_id']   = ['required', 'integer', Rule::exists('tdap_caminhoes', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}

class StoreCronoCaminhaoRequest extends AbstractCronoCaminhaoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

    protected function includeVinculo(): bool
    {
        return true;
    }
}

class UpdateCronoCaminhaoRequest extends AbstractCronoCaminhaoRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

    protected function includeVinculo(): bool
    {
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| Segmento: CronoViagem (registro e validacao de viagens)
|--------------------------------------------------------------------------
*/

class StoreCronoViagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.viagens.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'crono_caminhao_id' => ['required', 'integer', Rule::exists('tdap_crono_caminhoes', 'id')->whereNull('deleted_at')],
            'data_registro'     => ['required', 'date'],
            'obs'               => ['nullable', 'string', 'max:1000'],
        ];
    }
}

class ValidarCronoViagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.viagens.validar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'aprovada' => ['required', 'boolean'],
            'obs_aprovacao' => ['nullable', 'string', 'max:1000', 'required_if:aprovada,false'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'obs_aprovacao.required_if' => 'Ao rejeitar a viagem, informe o motivo.',
        ];
    }
}
