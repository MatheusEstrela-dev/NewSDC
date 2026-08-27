<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
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
            // hodometro_atual e nivel_combustivel NAO entram aqui: o estado
            // corrente da viatura e escrito exclusivamente pelo
            // MovimentacaoViaturaService (spec 3.1). Aceita-los na edicao abria
            // lost-update: o formulario e preenchido a partir da lista ja
            // renderizada, entao salvar numa tela obsoleta revertia o hodometro
            // e o combustivel que outra pessoa gravou ao registrar um retorno.
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
