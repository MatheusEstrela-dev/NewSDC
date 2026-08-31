<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantonistaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plantao.plantonistas.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'posto' => ['nullable', 'string', 'max:20'],
            'ativo' => ['required', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
