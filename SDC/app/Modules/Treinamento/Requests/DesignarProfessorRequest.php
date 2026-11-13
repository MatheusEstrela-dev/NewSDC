<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Services\ProfessorService;
use Illuminate\Foundation\Http\FormRequest;

class DesignarProfessorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(ProfessorService::PERMISSAO_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
        ];
    }
}
