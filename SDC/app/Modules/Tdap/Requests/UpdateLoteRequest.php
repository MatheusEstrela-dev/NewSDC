<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.lotes.edit') ?? false;
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
        return [
            'ata_id'       => ['required', 'integer', Rule::exists('tdap_atas', 'id')->whereNull('deleted_at')],
            'municipio_id' => ['required', 'integer', Rule::exists('municipios', 'id')],
            'prestador_id' => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'numero'       => ['required', 'string', 'max:20'],
            'nome'         => ['nullable', 'string', 'max:150'],
            'qtd_agua_m3'  => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'valor_m3'     => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'ativo'        => ['nullable', 'boolean'],
            'observacoes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $id = (int) $this->route('lote');
            $existe = \App\Modules\Tdap\Models\Lote::query()
                ->where('ata_id', $this->input('ata_id'))
                ->where('municipio_id', $this->input('municipio_id'))
                ->where('id', '!=', $id)
                ->whereNull('deleted_at')
                ->exists();

            if ($existe) {
                $v->errors()->add('municipio_id', 'Ja existe outro lote para esta Ata e Municipio.');
            }
        });
    }
}
