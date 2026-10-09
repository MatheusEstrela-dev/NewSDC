<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Models\PaeSimuladoAvaliacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AvaliarSimuladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    public function messages(): array
    {
        return self::mensagens();
    }

    /**
     * Fonte unica das regras, reutilizada pelo PaeSimuladoService fora do HTTP.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function regras(): array
    {
        return [
            'resultado' => ['required', Rule::in(PaeSimuladoAvaliacao::RESULTADOS)],
            'motivo_dispensa' => [
                'nullable',
                'required_if:resultado,dispensado',
                'prohibited_if:resultado,exigivel',
                Rule::in(PaeSimuladoAvaliacao::MOTIVOS_DISPENSA),
            ],
            'fundamentacao' => ['required', 'string', 'max:5000'],
            'num_sei' => ['required', 'string', 'max:100'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }

    /** @return array<string, string> */
    public static function mensagens(): array
    {
        return [
            'motivo_dispensa.required_if' => 'Informe o motivo da dispensa.',
            'motivo_dispensa.prohibited_if' => 'O motivo só se aplica à dispensa.',
        ];
    }
}
