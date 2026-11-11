<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Video;

use Illuminate\Foundation\Http\FormRequest;

class SalvarVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
        ];
    }
}
