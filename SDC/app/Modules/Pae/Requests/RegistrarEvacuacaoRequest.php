<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegistrarEvacuacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    public static function regras(): array
    {
        return [
            ...SimularEvacuacaoRequest::regras(),
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
