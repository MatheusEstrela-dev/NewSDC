<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarProgressoRequest extends FormRequest
{
    // A autorizacao de conteudo (inscricao aprovada e item liberado) vive no
    // TrilhaService; o acesso a rota ja e filtrado por middleware.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'posicao' => ['required', 'integer', 'min:1'],
        ];
    }
}
