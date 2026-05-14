<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCronogramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

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
        $id = (int) $this->route('cronograma');

        return [
            'numero' => [
                'required', 'string', 'max:20',
                Rule::unique('tdap_cronogramas', 'numero')->ignore($id)->whereNull('deleted_at'),
            ],
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
}
