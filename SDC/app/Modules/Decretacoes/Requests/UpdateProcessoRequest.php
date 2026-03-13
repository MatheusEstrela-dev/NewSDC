<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|string',
            'observacoes' => 'sometimes|string',
        ];
    }
}
