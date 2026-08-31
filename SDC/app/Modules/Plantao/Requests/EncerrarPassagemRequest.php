<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\NivelCombustivel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncerrarPassagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ocorrencias_destaque' => ['nullable', 'string', 'max:5000'],
            'snapshots' => ['required', 'array', 'min:1'],
            'snapshots.*.viatura_id' => ['required', 'integer', 'exists:plantao_viaturas,id'],
            'snapshots.*.hodometro' => ['required', 'integer', 'min:0'],
            'snapshots.*.nivel_combustivel' => ['required', Rule::enum(NivelCombustivel::class)],
            'snapshots.*.alteracoes' => ['nullable', 'string', 'max:2000'],
            'snapshots.*.anotacao' => ['nullable', 'string', 'max:160'],
            'snapshots.*.em_condicoes' => ['required', 'boolean'],
        ];
    }
}
