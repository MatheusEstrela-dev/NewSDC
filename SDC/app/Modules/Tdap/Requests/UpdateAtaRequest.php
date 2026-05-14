<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAtaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.atas.edit') ?? false;
    }

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
        $id = (int) $this->route('ata');

        return [
            'numero' => [
                'required', 'string', 'max:20',
                Rule::unique('tdap_atas', 'numero')->ignore($id)->whereNull('deleted_at'),
            ],
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
