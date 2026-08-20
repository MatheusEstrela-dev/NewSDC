<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use Illuminate\Foundation\Http\FormRequest;

/**
 * O legado aceitava decreto_id ou bi, e estourava erro de variavel indefinida
 * quando nenhum dos dois vinha. Aqui a ausencia dos dois e 422.
 */
final class ConsultaPedidoConsolidadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'decreto_id' => ['required_without:bi', 'nullable', 'string', 'max:60'],
            'bi'         => ['required_without:decreto_id', 'nullable'],
        ];
    }

    public function decretoId(): ?string
    {
        $valor = $this->validated('decreto_id');

        return $valor === null ? null : (string) $valor;
    }

    public function modoBi(): bool
    {
        return $this->decretoId() === null;
    }
}
