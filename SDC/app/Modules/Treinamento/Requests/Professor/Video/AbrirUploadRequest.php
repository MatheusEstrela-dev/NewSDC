<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Video;

use Illuminate\Foundation\Http\FormRequest;

class AbrirUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_original' => ['required', 'string', 'max:255'],
            'tamanho_total' => ['required', 'integer', 'min:1'],
            'mime' => ['required', 'string'],
        ];
    }
}
