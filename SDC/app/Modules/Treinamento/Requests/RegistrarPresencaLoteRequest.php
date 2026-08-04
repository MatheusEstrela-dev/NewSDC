<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF07 - lote de check-ins feitos offline e sincronizados quando a conexao
 * volta. Ver Components/Molecules/Treinamento/useOfflinePresenca.js no frontend.
 */
class RegistrarPresencaLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.presencas.registrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.qr_code_token' => ['required', 'uuid'],
            'itens.*.modulo_id' => ['required', 'integer'],
            'itens.*.confirmado_em' => ['nullable', 'date'],
        ];
    }
}
