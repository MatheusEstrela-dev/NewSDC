<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Enums\Urgencia;
use App\Modules\Demandas\Enums\Impacto;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class StoreDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.chamados.create');
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', new Enum(TipoDemanda::class)],
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'subcategoria' => ['nullable', 'string', 'max:100'],
            'urgencia' => ['nullable', new Enum(Urgencia::class)],
            'impacto' => ['nullable', new Enum(Impacto::class)],
            'responsavel_id' => [Rule::prohibitedIf(! $this->user()->can('demandas.chamados.manage')), 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
