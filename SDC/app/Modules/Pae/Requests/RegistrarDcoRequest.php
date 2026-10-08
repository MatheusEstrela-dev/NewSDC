<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegistrarDcoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras($this->input('competencia'));
    }

    public function messages(): array
    {
        return self::mensagens();
    }

    /**
     * Fonte unica das regras, reutilizada pelo PaeDcoService fora do HTTP.
     * A DCO da competencia N e emitida no ano N ou depois, nunca antes.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function regras(mixed $competencia = null): array
    {
        $anoMinimoDocumento = is_numeric($competencia) ? ['after_or_equal:'.((int) $competencia).'-01-01'] : [];

        return [
            'competencia' => ['required', 'integer', 'between:2022,'.CarbonImmutable::today()->year],
            'resultado' => ['required', Rule::in(['positiva', 'nao_conforme'])],
            'dt_documento' => ['required', 'date', 'before_or_equal:today', ...$anoMinimoDocumento],
            'dt_apresentacao' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:dt_documento'],
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'chave_idempotencia' => ['required', 'uuid'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mensagens(): array
    {
        return [
            'dt_documento.after_or_equal' => 'O ano da data do documento não pode ser anterior à competência.',
            'dt_apresentacao.after_or_equal' => 'A data de apresentação não pode ser anterior à data do documento.',
        ];
    }
}
