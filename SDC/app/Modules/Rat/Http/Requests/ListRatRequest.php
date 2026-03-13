<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Requests;

use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida e converte os parâmetros de filtro da listagem de RATs.
 *
 * SOLID - SRP: só valida input de filtro e converte para DTO.
 */
class ListRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validStatuses = array_column(Status::cases(), 'value');

        return [
            'protocolo'    => ['nullable', 'string', 'max:50'],
            'status'       => ['nullable', 'string', Rule::in($validStatuses)],
            'municipio'    => ['nullable', 'string', 'max:150'],
            'ano'          => ['nullable', 'digits:4', 'integer', 'min:2000', 'max:2099'],
            'data_inicio'  => ['nullable', 'date_format:Y-m-d'],
            'data_fim'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
            'tipo_cobrade' => ['nullable', 'string', 'max:20'],
            'natureza'     => ['nullable', 'string', 'max:150'],
            'criado_por'   => ['nullable', 'string', 'max:100'],
            'per_page'     => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function toFilterDTO(): RatFilterDTO
    {
        return RatFilterDTO::fromArray($this->validated());
    }
}
