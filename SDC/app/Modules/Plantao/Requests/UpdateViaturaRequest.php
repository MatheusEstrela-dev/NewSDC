<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateViaturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $viaturaId = $this->route('viatura')?->id;

        return [
            'prefixo' => ['required', 'string', 'max:20'],
            'placa' => [
                'required', 'string', 'max:10',
                Rule::unique('plantao_viaturas', 'placa')->ignore($viaturaId),
            ],
            'marca' => ['nullable', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:100'],
            'localizacao' => ['required', Rule::enum(LocalizacaoViatura::class)],
            'status' => ['required', Rule::enum(StatusViatura::class)],
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
