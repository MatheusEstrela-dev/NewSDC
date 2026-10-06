<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegistrarComunicacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'dt_envio' => ['required', 'date', 'before_or_equal:today'],
            'num_sei' => ['required', 'string', 'max:100'],
            'comprovante' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:20480'],
        ];
    }
}
