<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreViaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefixo' => ['required', 'string', 'max:20'],
            'placa' => ['required', 'string', 'max:10', 'unique:plantao_viaturas,placa'],
            'marca' => ['nullable', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:100'],
            'localizacao' => ['required', Rule::enum(LocalizacaoViatura::class)],
            'status' => ['required', Rule::enum(StatusViatura::class)],
            // Semeadura inicial, e so aqui. Uma viatura recem-cadastrada nao
            // tem movimentacao nenhuma no ledger, entao alguem precisa informar
            // o hodometro e o combustivel de partida. Dali em diante os dois
            // campos pertencem exclusivamente ao MovimentacaoViaturaService
            // (spec 3.1) - o UpdateViaturaRequest nao os aceita.
            'nivel_combustivel' => ['nullable', Rule::enum(NivelCombustivel::class)],
            'hodometro_atual' => ['nullable', 'integer', 'min:0'],
            'exclusiva_sobreaviso' => ['boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'ativo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => strtoupper(trim((string) $this->input('placa')))]);
        }
    }
}
