<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use App\Modules\Decretacoes\DTOs\ProcessoDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_processo' => 'sometimes|string|max:100',
            'municipio' => 'sometimes|string|max:255',
            'tipo_decreto' => 'sometimes|string',
            'municipios' => 'required|array',
            'municipios.*' => 'integer|exists:cedec_municipio,id',
            'data_entrada' => 'required|date',
            'processo' => 'required|string',
            'tipo_desastre_id' => 'required|integer',
            'informacoes_decreto' => 'nullable|string',
        ];
    }

    public function toDTO(): ProcessoDTO
    {
        return ProcessoDTO::fromRequest($this);
    }
}
