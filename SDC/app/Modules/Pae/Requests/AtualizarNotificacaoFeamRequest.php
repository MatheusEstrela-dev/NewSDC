<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarNotificacaoFeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dt_notificacao_feam' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'dt_notificacao_feam.before_or_equal' => 'A data da notificacao da FEAM nao pode ser futura.',
        ];
    }
}
