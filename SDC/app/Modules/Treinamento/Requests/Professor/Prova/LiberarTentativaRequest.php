<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Professor\Prova;

use Illuminate\Foundation\Http\FormRequest;

class LiberarTentativaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inscricao_id' => ['required', 'integer'],
            'quantidade' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
