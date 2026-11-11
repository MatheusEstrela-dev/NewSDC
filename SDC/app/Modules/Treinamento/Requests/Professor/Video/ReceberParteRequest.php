<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Video;

use Illuminate\Foundation\Http\FormRequest;

class ReceberParteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parte' => ['required', 'file'],
        ];
    }
}
