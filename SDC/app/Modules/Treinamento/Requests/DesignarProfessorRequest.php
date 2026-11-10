<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesignarProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.cursos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
        ];
    }
}
