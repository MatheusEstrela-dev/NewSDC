<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Pre-visualizacao dos indicios na tela, sem gravar: so tempos e alarme. */
final class PreviaIndiciosSimuladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    /** @return array<string, array<int, mixed>> */
    public static function regras(): array
    {
        return [...RegistrarSimuladoRequest::regrasTempos(), ...RegistrarSimuladoRequest::regrasAlarme()];
    }
}
