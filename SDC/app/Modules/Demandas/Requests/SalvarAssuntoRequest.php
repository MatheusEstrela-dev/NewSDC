<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Requests;

use App\Modules\Demandas\Services\ExecutarAutomacaoDemanda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarAssuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demandas.chamados.manage');
    }

    public function rules(): array
    {
        $assunto = $this->route('assunto');
        $sometimes = $assunto !== null ? ['sometimes'] : [];

        return [
            'nome' => [...$sometimes, 'required', 'string', 'max:150', Rule::unique('demanda_assuntos', 'nome')->ignore($assunto?->id)],
            'categoria_id' => ['sometimes', 'nullable', 'integer', 'exists:demanda_categorias,id'],
            'ativo' => ['sometimes', 'boolean'],
            'campos_dinamicos' => ['sometimes', 'nullable', 'array', 'max:20'],
            'campos_dinamicos.*.label' => ['required', 'string', 'max:100', 'distinct'],
            'campos_dinamicos.*.tipo' => ['required', Rule::in(['text', 'checkbox'])],
            'form_automacao' => ['sometimes', 'nullable', 'array'],
            'form_automacao.acao' => ['required_with:form_automacao', Rule::in(ExecutarAutomacaoDemanda::ACOES)],
            'form_automacao.campo_login' => ['required_with:form_automacao', 'string', Rule::in(
                collect($this->input('campos_dinamicos', $assunto?->campos_dinamicos ?? []))
                    ->where('tipo', 'text')->pluck('label')->all()
            )],
        ];
    }

    public function messages(): array
    {
        return ['form_automacao.campo_login.in' => 'O login deve vir de um campo de texto deste assunto.'];
    }
}
