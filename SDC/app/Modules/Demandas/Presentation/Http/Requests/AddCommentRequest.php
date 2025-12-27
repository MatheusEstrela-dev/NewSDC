<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentario' => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'comentario.required' => 'O comentário é obrigatório',
            'comentario.max' => 'O comentário não pode ter mais de 5000 caracteres',
        ];
    }
}
