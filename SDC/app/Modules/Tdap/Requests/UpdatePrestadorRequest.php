<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrestadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.prestadores.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj'  => $this->cleanDigits($this->input('cnpj')),
            'tel1'  => $this->cleanDigits($this->input('tel1')),
            'tel2'  => $this->cleanDigits($this->input('tel2')),
            'cep'   => $this->cleanDigits($this->input('cep')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'uf'    => $this->input('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = (int) $this->route('prestador');

        return [
            'cnpj' => [
                'required', 'string', 'size:14',
                Rule::unique('tdap_prestadores', 'cnpj')->ignore($id)->whereNull('deleted_at'),
            ],
            'nome'          => ['required', 'string', 'max:150'],
            'representante' => ['nullable', 'string', 'max:150'],
            'email'         => ['required', 'email', 'max:150'],
            'tel1'          => ['nullable', 'string', 'max:20'],
            'tel2'          => ['nullable', 'string', 'max:20'],
            'endereco'      => ['nullable', 'string', 'max:200'],
            'bairro'        => ['nullable', 'string', 'max:100'],
            'cidade'        => ['nullable', 'string', 'max:100'],
            'uf'            => ['nullable', 'string', 'size:2'],
            'cep'           => ['nullable', 'string', 'max:8'],
            'ativo'         => ['nullable', 'boolean'],
            'observacoes'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function cleanDigits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $clean === '' ? null : $clean;
    }
}
