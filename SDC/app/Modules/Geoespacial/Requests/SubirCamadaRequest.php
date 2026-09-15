<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirCamadaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // A validacao de tipo vem antes de qualquer leitura de conteudo: e
            // a primeira barreira da superficie de ataque.
            'arquivo' => [
                'required', 'file',
                'max:' . (int) config('geoespacial.upload_max_kb'),
                'extensions:kml,kmz',
            ],
            // Os metadados sao os mesmos da edicao, e vivem em RegrasDeMetadado:
            // enviar e editar validando cada um o seu jeito e como a tela de
            // edicao passa a aceitar o que o envio recusa.
            ...RegrasDeMetadado::regras($this->string('dominio')->toString() ?: null),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arquivo.extensions' => 'O arquivo precisa ser .kml ou .kmz.',
            ...RegrasDeMetadado::mensagens(),
        ];
    }
}
