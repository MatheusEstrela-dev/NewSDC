<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use App\Modules\Decretacoes\DTOs\ProcessoDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|string',
            'observacoes' => 'sometimes|string',
            'municipios' => 'sometimes|array',
            'municipios.*' => 'integer|exists:cedec_municipio,id',
            'data_entrada' => 'sometimes|date',
            'processo' => 'sometimes|string',
            'tipo_desastre_id' => 'sometimes|integer',
            'informacoes_decreto' => 'nullable|string',
        ];
    }

    public function toDTO(): ProcessoDTO
    {
        return ProcessoDTO::fromRequest($this);
    }
}
