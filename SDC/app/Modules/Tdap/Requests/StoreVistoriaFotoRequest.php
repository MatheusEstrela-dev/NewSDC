<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVistoriaFotoRequest extends FormRequest
{
    /** Quantas fotos cabem num envio. */
    public const MAX_POR_LOTE = 10;

    /**
     * Regras de um arquivo de foto, compartilhadas com o cadastro da vistoria
     * -- que aceita as fotos no mesmo POST. Duplicar a lista faria a tela de
     * criacao e a de edicao aceitarem formatos diferentes.
     *
     * heic/heif entram porque e o que o iPhone manda por padrao; sem eles o
     * vistoriador em campo leva "arquivo invalido" sem entender. `image`
     * sozinho nao cobre heic.
     *
     * @var list<string>
     */
    public const REGRAS_DO_ARQUIVO = ['file', 'max:15360', 'mimes:jpg,jpeg,png,webp,heic,heif'];

    /**
     * As mensagens andam com as regras.
     *
     * Elas viviam so aqui, embora a regra ja fosse compartilhada: a mesma foto
     * de 20 MB recusada no cadastro da vistoria caia na mensagem generica do
     * Laravel ("The fotos.0 field must not be greater than 15360 kilobytes"),
     * e na tela de anexo recebia a frase em portugues. Mesma regra, duas
     * explicacoes -- e a pior delas na tela onde o vistoriador esta em campo.
     *
     * @var array<string, string>
     */
    public const MENSAGENS_DO_ARQUIVO = [
        'fotos.*.mimes' => 'Envie imagens JPG, PNG, WEBP ou HEIC.',
        'fotos.*.max'   => 'Cada foto deve ter no maximo 15 MB.',
        'fotos.max'     => 'Envie no maximo 10 fotos por vez.',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('tdap.vistorias.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fotos'     => ['required', 'array', 'min:1', 'max:'.self::MAX_POR_LOTE],
            'fotos.*'   => self::REGRAS_DO_ARQUIVO,
            'descricao' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fotos.*' => 'foto',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::MENSAGENS_DO_ARQUIVO;
    }
}
