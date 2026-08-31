<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AceitarPassagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acao' => ['required', 'in:aceitar,divergencia'],
            // Divergencia sem texto nao serve para nada: o proximo turno precisa
            // saber o que nao conferiu.
            'divergencia' => ['required_if:acao,divergencia', 'nullable', 'string', 'max:2000'],
        ];
    }
}
