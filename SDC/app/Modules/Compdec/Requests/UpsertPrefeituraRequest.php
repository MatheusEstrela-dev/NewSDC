<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Requests;

use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Http\FormRequest;

class UpsertPrefeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        $orgao = $this->route('orgao');

        if (! $orgao instanceof Orgao) {
            return false;
        }

        return $this->user()?->can('update', new Prefeitura()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prefeito_nome' => ['nullable', 'string', 'max:255'],
            'prefeito_telefone' => ['nullable', 'string', 'max:20'],
            'prefeito_celular' => ['nullable', 'string', 'max:20'],
            'prefeito_email' => ['nullable', 'email', 'max:255'],
            'endereco' => ['nullable', 'string'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cep' => ['nullable', 'string', 'max:10', 'regex:/^\d{5}-?\d{3}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'inss_tem_cobranca' => ['nullable', 'boolean'],
            'inss_aliquota' => ['nullable', 'numeric', 'between:0,100'],
            'inss_lei_cobranca' => ['nullable', 'string', 'max:120'],
            'inss_responsavel' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cep.regex' => 'CEP deve estar no formato 00000-000 ou 00000000.',
        ];
    }
}
