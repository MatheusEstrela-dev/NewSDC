<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacao do update da prefeitura pela tela estadual da CEDEC.
 *
 * Os indicadores municipais -- populacao, pop_rural, area, macrorregiao, territorio,
 * distancia_bh, qtd_pipa -- NAO entram aqui de proposito: sao dado de municipio, nao
 * de prefeitura, e ficam read-only nesta fase. Acrescentar um deles abriria caminho
 * para a tela sobrescrever o espelho do legado.
 *
 * legacy_id tambem nao entra: e a ponte com o registro de origem e so o ETL escreve
 * nela.
 */
final class UpdatePrefeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cedec.prefeituras.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'prefeito_nome' => ['nullable', 'string', 'max:255'],
            'prefeito_partido' => ['nullable', 'string', 'max:60'],
            'prefeito_telefone' => ['nullable', 'string', 'max:20'],
            'prefeito_celular' => ['nullable', 'string', 'max:20'],
            'prefeito_email' => ['nullable', 'email', 'max:255'],
            'email_prefeitura' => ['nullable', 'email', 'max:255'],
            'email_prefeitura_2' => ['nullable', 'email', 'max:255'],
            'email_prefeitura_3' => ['nullable', 'email', 'max:255'],
            'tel_prefeitura' => ['nullable', 'string', 'max:20'],
            'tel_prefeitura_2' => ['nullable', 'string', 'max:20'],
            'fax_prefeitura' => ['nullable', 'string', 'max:20'],
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

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cep.regex' => 'CEP deve estar no formato 00000-000 ou 00000000.',
        ];
    }
}
