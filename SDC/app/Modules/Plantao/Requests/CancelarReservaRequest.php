<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelarReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:200'],
        ];
    }
}
