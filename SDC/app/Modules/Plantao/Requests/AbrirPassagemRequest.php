<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AbrirPassagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date'],
            // Valida contra a tabela, nao contra enum: os horarios agora sao
            // cadastraveis e um enum voltaria a exigir deploy a cada turno novo.
            'periodo' => [
                'required',
                Rule::exists('plantao_tipos_turno', 'codigo')->where('ativo', true),
            ],
            // Localizacao do turno no relatorio (ex.: Predio Alterosas). Ausente,
            // o service aplica o padrao.
            'localizacao' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * `plantonista_id` NAO entra nas regras de proposito: quem abre o turno e
     * sempre o usuario autenticado. Aceita-lo do cliente deixaria qualquer um
     * abrir turno em nome de terceiro.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('data')) {
            $this->merge(['data' => trim((string) $this->input('data'))]);
        }
    }
}
