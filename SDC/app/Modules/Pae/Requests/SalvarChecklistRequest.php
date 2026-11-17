<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Support\Checklist\ChecklistGmg83;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SalvarChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'respostas' => ['present', 'array', 'max:200'],
            'respostas.*.codigo' => ['required', 'string', 'max:10', 'distinct'],
            'respostas.*.resposta' => ['nullable', Rule::in(ChecklistGmg83::RESPOSTAS)],
            'respostas.*.observacao' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'respostas.*.observacao' => 'observação',
        ];
    }
}
