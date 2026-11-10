<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor;

use Illuminate\Foundation\Http\FormRequest;

class ReordenarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }
}
