<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Requests;

use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPresencaManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('treinamento.presencas.registrar') ?? false;
    }

    public function rules(): array
    {
        return [
            'inscricao_id' => ['required', 'integer', Rule::exists(Inscricao::class, 'id')],
            'modulo_id' => ['required', 'integer', Rule::exists(Modulo::class, 'id')],
        ];
    }
}
