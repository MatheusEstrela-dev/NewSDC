<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirNotificacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'num_sei' => ['required', 'string', 'max:110'],
            'obs'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
