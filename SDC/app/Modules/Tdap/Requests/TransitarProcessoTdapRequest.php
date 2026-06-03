<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Enums\EstadoProcesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TransitarProcessoTdapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.processos.transitar') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado_alvo' => ['required', new Enum(EstadoProcesso::class)],
            'motivo'      => ['nullable', 'string', 'max:2000'],
        ];
    }
}
