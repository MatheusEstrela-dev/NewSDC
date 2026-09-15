<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'viatura_id' => ['required', 'integer', 'exists:plantao_viaturas,id'],
            'inicio_previsto' => ['required', 'date'],
            // A ordem entre as duas datas e a duracao maxima sao guardas de
            // dominio no ReservaViaturaService, nao regra de formulario: elas
            // valem para qualquer caminho de entrada, inclusive o console.
            'fim_previsto' => ['required', 'date'],
            'destino' => ['nullable', 'string', 'max:160'],
            'motivo' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'viatura_id.required' => 'Escolha a viatura.',
            'viatura_id.exists' => 'Viatura nao encontrada.',
            'inicio_previsto.required' => 'Informe o inicio da reserva.',
            'fim_previsto.required' => 'Informe o fim da reserva.',
        ];
    }
}
