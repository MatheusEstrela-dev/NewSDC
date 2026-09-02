<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanChaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Sem `exists`: token invalido nao e erro de formulario, e recusa
            // de dominio com texto que o agente le na tela do scanner
            // ("Etiqueta nao reconhecida"). Um 422 de validacao generico ali
            // nao diz nada a quem esta com o celular apontado para a chave.
            'qr_token' => ['required', 'string', 'max:64'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('qr_token')) {
            $this->merge(['qr_token' => trim((string) $this->input('qr_token'))]);
        }
    }
}
