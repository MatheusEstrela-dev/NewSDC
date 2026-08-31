<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AjudaHumanitaria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacao identica a do endpoint pubajudah do legado.
 *
 * O enum de evento confere com o dado real: as 3.582 liberacoes carregadas se
 * distribuem exatamente nestes seis valores.
 */
final class ConsultaLiberacaoApiRequest extends FormRequest
{
    /** @var list<string> */
    public const EVENTOS = [
        'AJUDA HUMANITARIA',
        'CEDEC',
        'CHUVA',
        'COVID-19',
        'OUTROS',
        'SECA',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ano_comeco' => ['required', 'integer', 'digits:4', 'min:1900'],
            'ano_fim'    => ['sometimes', 'integer', 'digits:4', 'min:1900', 'max:' . date('Y')],
            'evento'     => ['sometimes', Rule::in(self::EVENTOS)],
        ];
    }

    public function anoComeco(): int
    {
        return (int) $this->validated('ano_comeco');
    }

    public function anoFim(): ?int
    {
        $valor = $this->validated('ano_fim');

        return $valor === null ? null : (int) $valor;
    }

    public function evento(): ?string
    {
        $valor = $this->validated('evento');

        return $valor === null ? null : (string) $valor;
    }
}
