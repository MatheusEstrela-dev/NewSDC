<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

abstract class AbstractVistoriaRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nome'        => ['required', 'string', 'max:150'],
            'edital'      => ['nullable', 'string', 'max:50'],
            'lote'        => ['nullable', 'string', 'max:30'],
            'placa_id'    => ['required', 'integer', Rule::exists('tdap_caminhoes', 'id')->whereNull('deleted_at')],
            'modelo'      => ['nullable', 'string', 'max:50'],
            'cor'         => ['nullable', 'string', 'max:30'],
            'data'        => ['required', 'date'],
            'ano'         => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'capacidade'  => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'parecer'     => ['required', new Enum(ParecerVistoria::class)],
            'ficha'       => ['nullable', 'string', 'max:50'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (Vistoria::ITENS_ESTRUTURAIS as $campo) {
            $rules[$campo] = ['nullable', 'boolean'];
            $rules["{$campo}_obs"] = ['nullable', 'string', 'max:500'];
        }
        foreach (Vistoria::ITENS_TANQUE as $campo) {
            $rules[$campo] = ['nullable', 'boolean'];
            $rules["{$campo}_obs"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ano.regex' => 'Ano deve ter 4 dígitos numéricos.',
        ];
    }
}
