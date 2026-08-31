<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractCaminhaoRequest extends FormRequest
{
    abstract protected function placaUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        $placa = $this->input('placa');
        $this->merge([
            'placa' => $placa
                ? mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $placa) ?? '')
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prestador_id'  => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            // A placa chega sem separador (prepareForValidation) e tem 7
            // caracteres nos dois padroes: AAA1111 (antigo) e AAA1A11
            // (Mercosul). O `max:8` de antes nunca podia ser exercido -- o
            // regex ja recusava o 8o caractere -- e so confundia a leitura.
            'placa'         => ['required', 'string', 'size:7', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $this->placaUniqueRule()],
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
