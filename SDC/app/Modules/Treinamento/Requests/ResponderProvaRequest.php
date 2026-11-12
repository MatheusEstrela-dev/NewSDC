<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderProvaRequest extends FormRequest
{
    // Quem pode responder (inscricao aprovada, tentativa propria e aberta) e
    // decidido no ProvaService; o acesso a rota ja e filtrado por middleware.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questao_id' => ['required', 'integer', 'min:1'],
            'alternativa_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
