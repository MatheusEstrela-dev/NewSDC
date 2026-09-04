<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'dominio' => ['required', Rule::in(array_keys((array) config('geoespacial.dominios')))],
            'nome' => ['required', 'string', 'max:255'],
            // Emissao, validade e nivel NAO existem dentro do KML -- so no nome
            // do arquivo. Extrair de nome de arquivo externo e contrato que
            // ninguem garante, entao o operador informa.
            'emitido_em' => ['required', 'date'],
            'valido_ate' => ['nullable', 'date', 'after_or_equal:emitido_em'],
            'nivel' => ['required', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arquivo.extensions' => 'O arquivo precisa ser .kml ou .kmz.',
            'dominio.in' => 'Dominio desconhecido.',
        ];
    }
}
