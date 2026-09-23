<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\Impacto;
use Illuminate\Validation\Rules\Enum;

class UpdateDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demanda = \App\Modules\Demandas\Models\Demanda::find($this->route('id'));
        return $demanda !== null && $this->user()->can('update', $demanda);
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', new Enum(TipoDemanda::class)],
            'urgencia' => ['sometimes', new Enum(Urgencia::class)],
            'impacto' => ['sometimes', new Enum(Impacto::class)],
            'titulo' => ['sometimes', 'string', 'max:255'],
            'descricao' => ['sometimes', 'string'],
            'categoria' => ['sometimes', 'nullable', 'string', 'max:100'],
            'subcategoria' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
