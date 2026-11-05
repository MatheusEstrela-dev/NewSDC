<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Requests;

use App\Modules\NotasTecnicas\Models\NotaTecnica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Envio de um PDF como nova versao de uma nota existente. A autorizacao fica
 * no middleware da rota (notas_tecnicas.notas.edit).
 */
class StoreNotaTecnicaVersaoRequest extends FormRequest
{
    public const NUMERO_REGEX = '/^\d{1,3}(\.\d{1,3}){0,2}$/';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var NotaTecnica|null $nota */
        $nota = $this->route('nota');

        return [
            'arquivo' => self::regraArquivo(),
            'numero' => [
                'required',
                'string',
                'max:20',
                'regex:' . self::NUMERO_REGEX,
                Rule::unique('nota_tecnica_versoes', 'numero')->where('nota_tecnica_id', $nota?->id),
            ],
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
            'numero.unique' => 'Esta nota já tem uma versão com esse número.',
        ];
    }

    /** @return list<string> */
    public static function regraArquivo(): array
    {
        $maxKb = (int) (config('notas_tecnicas.upload_max_bytes') / 1024);

        return ['required', 'file', 'mimes:pdf', 'max:' . $maxKb];
    }
}
