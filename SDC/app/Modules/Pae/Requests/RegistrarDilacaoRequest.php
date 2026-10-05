<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDilacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dias_adicionais' => ['required', 'integer', 'min:1', 'max:365'],
            'justificativa' => ['required', 'string', 'max:2000'],
        ];
    }
}
