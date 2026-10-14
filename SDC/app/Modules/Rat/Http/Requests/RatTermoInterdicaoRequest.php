<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatTermoInterdicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permissao fina (COMPDEC x agente CREA) depende da ocorrencia e fica
        // no RatTermoInterdicaoService.
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'             => ['sometimes', 'nullable', 'in:parcial,total'],
            'area_interditada' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'fundamentacao'    => ['sometimes', 'nullable', 'string', 'max:5000'],
            'medidas'          => ['sometimes', 'nullable', 'string', 'max:5000'],
            'prazo_dias'       => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
            'responsavel_nome' => ['sometimes', 'nullable', 'string', 'max:191'],
            'registro_crea'    => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
