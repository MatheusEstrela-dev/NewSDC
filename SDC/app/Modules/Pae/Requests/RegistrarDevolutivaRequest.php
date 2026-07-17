<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDevolutivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dt_devolutiva' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
