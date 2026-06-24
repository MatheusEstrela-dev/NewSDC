<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePmdaPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pmda.planos.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
        ];
    }
}
