<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests\Gestao;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Vagas do curso inteiro (null = ilimitado). Autorizacao fica na rota.
 */
class VagasUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_vagas' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
