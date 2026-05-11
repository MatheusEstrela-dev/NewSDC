<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaminhaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.caminhoes.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'placa' => $this->input('placa')
                ? mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('placa')) ?? '')
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prestador_id' => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'placa' => [
                'required', 'string', 'min:7', 'max:8',
                'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/',
                Rule::unique('tdap_caminhoes', 'placa')->whereNull('deleted_at'),
            ],
            'marca'         => ['nullable', 'string', 'max:50'],
            'modelo'        => ['nullable', 'string', 'max:50'],
            'cor'           => ['nullable', 'string', 'max:30'],
            'ano'           => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'capacidade_m3' => ['required', 'numeric', 'min:0.5', 'max:999.99'],
            'ativo'         => ['nullable', 'boolean'],
            'observacoes'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'placa.regex' => 'Placa deve seguir o padrão Mercosul (AAA1A11) ou antigo (AAA1111).',
            'ano.regex'   => 'Ano deve ter 4 dígitos numéricos.',
        ];
    }
}
