<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEscalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plantao.escala.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'ano' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
