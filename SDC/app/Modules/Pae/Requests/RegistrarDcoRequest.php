<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegistrarDcoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return [
            'competencia' => ['required', 'integer', 'between:2022,'.now()->year],
            'resultado' => ['required', Rule::in(['positiva', 'nao_conforme'])],
            'dt_documento' => ['required', 'date', 'before_or_equal:today'],
            'dt_apresentacao' => ['required', 'date', 'before_or_equal:today'],
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'chave_idempotencia' => ['required', 'uuid'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
