<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Requests;

use App\Modules\Plantao\Enums\PeriodoPlantao;
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
            'periodo' => ['required', Rule::enum(PeriodoPlantao::class)],
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
