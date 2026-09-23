<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Demandas\Enums\StatusDemanda;
use Illuminate\Validation\Rules\Enum;

class UpdateDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.chamados.edit');
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(StatusDemanda::class)],
            'titulo' => ['sometimes', 'string', 'max:255'],
            'descricao' => ['sometimes', 'string'],
            'categoria' => ['sometimes', 'nullable', 'string', 'max:100'],
            'subcategoria' => ['sometimes', 'nullable', 'string', 'max:100'],
            'responsavel_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
