<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Models\Lote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class AbstractLoteRequest extends FormRequest
{
    /**
     * ID a ignorar na validacao composta (ata_id, municipio_id).
     * Store: null.
     * Update: id do lote sendo editado.
     */
    abstract protected function ignoreId(): ?int;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $ataId = (int) $this->input('ata_id');
            $municipioId = (int) $this->input('municipio_id');
            if ($ataId === 0 || $municipioId === 0) {
                return;
            }

            $query = Lote::query()
                ->where('ata_id', $ataId)
                ->where('municipio_id', $municipioId)
                ->whereNull('deleted_at');

            if ($id = $this->ignoreId()) {
                $query->where('id', '!=', $id);
            }

            if ($query->exists()) {
                $msg = $this->ignoreId()
                    ? 'Ja existe outro lote para esta Ata e Municipio.'
                    : 'Ja existe um lote para esta Ata e Municipio.';
                $v->errors()->add('municipio_id', $msg);
            }
        });
    }
}
