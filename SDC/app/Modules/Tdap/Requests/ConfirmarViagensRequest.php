<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarViagensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.viagens.confirmar') ?? false;
    }

    /**
     * Valida FORMATO, nao pertencimento.
     *
     * Um `exists` aqui so garantiria que a viagem existe -- nao que ela e do
     * municipio de quem esta confirmando. Esse recorte e feito na consulta do
     * service (doMunicipio), que e o unico lugar onde nao da para contornar
     * trocando um id no navegador.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids'             => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'           => ['integer', 'min:1'],
            'obs_confirmacao' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Selecione ao menos uma viagem para confirmar.',
            'ids.max'      => 'Confirme no maximo 200 viagens por vez.',
        ];
    }
}
