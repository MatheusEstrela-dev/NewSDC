<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Requests;

/**
 * Cadastro de nota: os dados descritivos mais o primeiro PDF. Nasce como
 * rascunho; publicar e um passo separado.
 */
class StoreNotaTecnicaRequest extends UpdateNotaTecnicaRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'arquivo' => StoreNotaTecnicaVersaoRequest::regraArquivo(),
            'numero' => ['required', 'string', 'max:20', 'regex:' . StoreNotaTecnicaVersaoRequest::NUMERO_REGEX],
            'changelog' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'arquivo.mimes' => 'Envie o documento em PDF.',
            'arquivo.max' => 'O PDF passa do limite permitido.',
            'numero.regex' => 'Use números separados por ponto, como 1.0 ou 2.1.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [...parent::attributes(), 'arquivo' => 'PDF', 'numero' => 'versão'];
    }
}
