<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

/**
 * Base SOLID/DRY: regras comuns Prestador (Fase 1 TDAP).
 *
 * Single Responsibility: define APENAS as regras de dominio do Prestador.
 * Open/Closed: Store/Update estendem; novos contextos (ex: ImportPrestador)
 *   estendem sem mexer no abstract.
 * Liskov: subclasses respeitam o contrato uniqueRule().
 * Interface Segregation: cada subclasse expoe so o necessario via authorize().
 * Dependency Inversion: rules() depende da abstracao uniqueRule().
 */
abstract class AbstractPrestadorRequest extends FormRequest
{
    /**
     * Regra de unicidade do CNPJ.
     * Store: sem ignore.
     * Update: ignore($id).
     */
    abstract protected function cnpjUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj'  => self::cleanDigits($this->input('cnpj')),
            'tel1'  => self::cleanDigits($this->input('tel1')),
            'tel2'  => self::cleanDigits($this->input('tel2')),
            'cep'   => self::cleanDigits($this->input('cep')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'uf'    => $this->input('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cnpj'          => ['required', 'string', 'size:14', $this->cnpjUniqueRule()],
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

    final protected static function cleanDigits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $clean === '' ? null : $clean;
    }
}
