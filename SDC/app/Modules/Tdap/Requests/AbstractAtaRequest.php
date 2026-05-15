<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractAtaRequest extends FormRequest
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
            'numero'      => ['required', 'string', 'max:20', $this->numeroUniqueRule()],
            'dt_inicio'   => ['required', 'date'],
            'dt_final'    => ['required', 'date', 'after_or_equal:dt_inicio'],
            'historico'   => ['nullable', 'string', 'max:5000'],
            'ativo'       => ['nullable', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dt_final.after_or_equal' => 'Data final deve ser igual ou posterior à data inicial.',
        ];
    }
}
