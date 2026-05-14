<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCronoCaminhaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.cronogramas.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comunidade_id' => ['nullable', 'integer'],
            'agua_prevista' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'num_viagens'   => ['required', 'integer', 'min:1', 'max:10000'],
            'ordem'         => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
    }
}
