<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Prova;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurarProvaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nota_minima' => ['required', 'numeric', 'between:0,100'],
            'max_tentativas' => ['required', 'integer', 'between:1,20'],
        ];
    }
}
