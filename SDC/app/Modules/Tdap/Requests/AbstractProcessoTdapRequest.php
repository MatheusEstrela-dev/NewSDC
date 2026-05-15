<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractProcessoTdapRequest extends FormRequest
{
    abstract protected function numeroUniqueRule(): Unique;

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
            'numero'       => ['required', 'string', 'max:20', $this->numeroUniqueRule()],
            'municipio_id' => ['required', 'integer', Rule::exists('municipios', 'id')],
            'contexto'     => ['nullable', 'array'],
        ];
    }
}
