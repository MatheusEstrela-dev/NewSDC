<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Models\Lote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class AbstractLoteRequest extends FormRequest
{
    /**
     * ID a ignorar na validacao de unicidade (ata_id, numero).
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
            // Um lote atende varios municipios (ver tdap_lote_municipios).
            'municipio_ids'   => ['required', 'array', 'min:1'],
            'municipio_ids.*' => ['integer', 'distinct', Rule::exists('municipios', 'id')],
            'prestador_id' => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'numero'       => ['required', 'string', 'max:20'],
            'nome'         => ['nullable', 'string', 'max:150'],
            'contrato'     => ['nullable', 'string', 'max:50'],
            'qtd_agua_m3'  => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'valor_m3'     => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'ativo'        => ['nullable', 'boolean'],
            'observacoes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // O numero identifica o lote DENTRO da ata (L01, L02...). Sem esta
        // checagem dois lotes da mesma ata podiam nascer com o mesmo numero.
        $validator->after(function (Validator $v): void {
            $ataId = (int) $this->input('ata_id');
            $numero = trim((string) $this->input('numero'));
            if ($ataId === 0 || $numero === '') {
                return;
            }

            $query = Lote::query()
                ->where('ata_id', $ataId)
                ->where('numero', mb_strtoupper($numero))
                ->whereNull('deleted_at');

            if ($id = $this->ignoreId()) {
                $query->where('id', '!=', $id);
            }

            if ($query->exists()) {
                $v->errors()->add('numero', "Ja existe um lote {$numero} nesta Ata.");
            }
        });

        // Municipio com cronograma emitido no lote nao pode sair da lista: o
        // cronograma aponta para lote + municipio e ficaria orfao da relacao.
        $validator->after(function (Validator $v): void {
            $loteId = $this->ignoreId();

            if ($loteId === null) {
                return;
            }

            $selecionados = array_map('intval', (array) $this->input('municipio_ids', []));

            $emUso = DB::table('tdap_cronogramas')
                ->where('lote_id', $loteId)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('municipio_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $removidos = array_diff($emUso, $selecionados);

            if ($removidos === []) {
                return;
            }

            $nomes = DB::table('municipios')
                ->whereIn('id', $removidos)
                ->orderBy('nome')
                ->pluck('nome')
                ->implode(', ');

            $v->errors()->add(
                'municipio_ids',
                "Estes municipios possuem cronograma neste lote e nao podem ser removidos: {$nomes}.",
            );
        });
    }
}
