<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Models\Lote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

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
            'fator'                 => ['nullable', 'numeric', 'min:0.01', 'max:99.99'],
            'dt_inicio'             => ['required', 'date'],
            'dt_final'              => ['required', 'date', 'after_or_equal:dt_inicio'],
            'justificativa'         => ['nullable', 'string', 'max:5000'],
            'nota_empenho'          => ['nullable', 'string', 'max:50'],
            'ponto_captacao_id'     => ['nullable', 'integer'],
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
            if ((int) $lote->municipio_id !== (int) $this->input('municipio_id')) {
                $v->errors()->add('municipio_id', 'O Municipio nao confere com o Lote escolhido.');
            }
            if ((int) $lote->prestador_id !== (int) $this->input('prestador_id')) {
                $v->errors()->add('prestador_id', 'O Prestador nao confere com o Lote escolhido.');
            }
        });
    }
}
