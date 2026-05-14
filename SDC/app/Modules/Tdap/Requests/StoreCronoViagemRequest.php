<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCronoViagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.viagens.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'crono_caminhao_id' => ['required', 'integer', Rule::exists('tdap_crono_caminhoes', 'id')->whereNull('deleted_at')],
            'data_registro'     => ['required', 'date'],
            'obs'               => ['nullable', 'string', 'max:1000'],
        ];
    }
}
