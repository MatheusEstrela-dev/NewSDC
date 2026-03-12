<?php

declare(strict_types=1);

namespace App\Modules\Rat\Requests;

use App\Modules\Rat\DTO\RatFilterDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida e converte os parâmetros de filtro da listagem de ocorrências RAT.
 */
class ListRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_bos'  => ['nullable', 'string', 'max:50'],
            'status'      => ['nullable', 'integer', 'in:0,1'],
            'data_inicio' => ['nullable', 'date_format:Y-m-d'],
            'data_fim'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
            'per_page'    => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function toFilterDTO(): RatFilterDTO
    {
        return RatFilterDTO::fromArray($this->validated());
    }
}
