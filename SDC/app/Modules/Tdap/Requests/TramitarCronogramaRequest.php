<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Enums\AcaoTramite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A permissao de cada acao depende da fase do cronograma, entao a
 * autorizacao fina fica no CronogramaWorkflow, nao aqui.
 */
class TramitarCronogramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'acao'       => ['required', Rule::enum(AcaoTramite::class)],
            'observacao' => [
                Rule::requiredIf(fn () => $this->input('acao') === AcaoTramite::Devolver->value),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'observacao.required' => 'Informe o motivo da devolução.',
        ];
    }

    public function acao(): AcaoTramite
    {
        return AcaoTramite::from((string) $this->validated('acao'));
    }
}
