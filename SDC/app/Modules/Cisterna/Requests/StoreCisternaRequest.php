<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Enums\StatusCisterna;
use App\Modules\Cisterna\Enums\TipoCisterna;
use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCisternaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cisterna::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'integer', Rule::exists('municipios', 'id')],
            'codigo' => ['required', 'string', 'max:60', Rule::unique('cisternas', 'codigo')],
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', new Enum(TipoCisterna::class)],
            'status' => ['nullable', new Enum(StatusCisterna::class)],
            'endereco' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacidade_litros' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'data_instalacao' => ['nullable', 'date'],
            'responsavel_nome' => ['nullable', 'string', 'max:255'],
            'responsavel_telefone' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string'],
        ];
    }
}
