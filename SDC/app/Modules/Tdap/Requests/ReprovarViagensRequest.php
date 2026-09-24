<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReprovarViagensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.viagens.reprovar') ?? false;
    }

    /**
     * Mesmo recorte de ConfirmarViagensRequest: aqui so o FORMATO; o
     * pertencimento ao municipio e o limite do cronograma ficam no service.
     *
     * O motivo e obrigatorio, ao contrario da observacao da confirmacao: a
     * reprovacao impede o pagamento, e a CEDEC e o prestador precisam saber
     * por que.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids'    => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'  => ['integer', 'min:1'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required'    => 'Selecione ao menos uma viagem para reprovar.',
            'ids.max'         => 'Reprove no maximo 200 viagens por vez.',
            'motivo.required' => 'Informe o motivo da reprovacao.',
            'motivo.min'      => 'Descreva o motivo da reprovacao com ao menos 5 caracteres.',
        ];
    }
}
