<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaNotificacao::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notificavel_type' => ['required', Rule::in(array_keys(NotificacaoDTO::TIPOS_PERMITIDOS))],
            'notificavel_id' => ['required', 'integer', 'min:1'],
            'observacao' => ['required', 'string', 'max:2000'],
            'arquivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notificavel_type.required' => 'Informe sobre qual registro e a notificacao.',
            'notificavel_type.in' => 'Tipo de registro invalido para notificacao.',
            'observacao.required' => 'Descreva a notificacao.',
        ];
    }
}
