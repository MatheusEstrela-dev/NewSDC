<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Requests\Concerns\ResolveIdDaRota;
use App\Modules\Tdap\Support\Documento;
use App\Rules\CnpjValido;
use App\Support\UnidadeFederativa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/*
|--------------------------------------------------------------------------
| Requests centralizados do dominio Prestador (TDAP)
|--------------------------------------------------------------------------
| Arquivo unico mapeado via classmap (composer.json). Classe abstrata vem
| ANTES das subclasses, pois o PHP declara as classes deste arquivo na ordem
| em que aparecem; uma subclasse antes da pai causa fatal error.
*/

/**
 * Base SOLID/DRY: regras comuns Prestador (Fase 1 TDAP).
 *
 * Single Responsibility: define APENAS as regras de dominio do Prestador.
 * Open/Closed: Store/Update estendem; novos contextos (ex: ImportPrestador)
 *   estendem sem mexer no abstract.
 * Liskov: subclasses respeitam o contrato uniqueRule().
 * Interface Segregation: cada subclasse expoe so o necessario via authorize().
 * Dependency Inversion: rules() depende da abstracao uniqueRule().
 */
abstract class AbstractPrestadorRequest extends FormRequest
{
    /**
     * Regra de unicidade do CNPJ.
     * Store: sem ignore.
     * Update: ignore($id).
     */
    abstract protected function cnpjUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        $normalizado = [
            'cnpj'  => self::cleanDigits($this->input('cnpj')),
            'tel1'  => self::cleanDigits($this->input('tel1')),
            'tel2'  => self::cleanDigits($this->input('tel2')),
            'cep'   => self::cleanDigits($this->input('cep')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'uf'    => $this->input('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
        ];

        /*
         * `ativo` chega de tres formas: boolean (Inertia manda JSON), '1'/'0'
         * (formulario tradicional) e '' -> null (ConvertEmptyStringsToNull, com
         * checkbox desmarcado). Sem esta coercao o null caia no
         * `(bool) ($data['ativo'] ?? true)` do DTO e REATIVAVA o prestador que
         * o usuario acabou de desmarcar.
         *
         * A chave so e mexida se veio no payload: ausente segue como "nao
         * informado" e o DTO aplica o default de cadastro novo (ativo).
         */
        if ($this->has('ativo')) {
            $normalizado['ativo'] = $this->boolean('ativo');
        }

        $this->merge($normalizado);
    }

    /**
     * As regras rodam sobre o payload JA normalizado por prepareForValidation:
     * cnpj/tel/cep chegam aqui como digitos puros, por isso os tamanhos sao
     * contados em digitos (cnpj 14, cep 8) e nao no formato mascarado.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Alem do tamanho, o digito verificador: CNPJ de 14 digitos
            // inventado (ex.: 12121212121212) entrava no cadastro e depois
            // reaparecia em contrato, empenho e e-mail ao prestador.
            'cnpj'          => ['required', 'string', 'size:14', new CnpjValido, $this->cnpjUniqueRule()],
            'nome'          => ['required', 'string', 'max:150'],
            'representante' => ['nullable', 'string', 'max:150'],
            'email'         => ['required', 'email', 'max:150'],
            // 10 digitos (fixo) ou 11 (celular). O 'max:20' antigo aceitava
            // numero truncado e placeholder de mascara vazado do front.
            'tel1'          => ['nullable', 'string', 'digits_between:10,11'],
            'tel2'          => ['nullable', 'string', 'digits_between:10,11'],
            'endereco'      => ['nullable', 'string', 'max:200'],
            'bairro'        => ['nullable', 'string', 'max:100'],
            'cidade'        => ['nullable', 'string', 'max:100'],
            'uf'            => ['nullable', 'string', 'size:2', Rule::in(UnidadeFederativa::siglas())],
            'cep'           => ['nullable', 'string', 'digits:8'],
            'ativo'         => ['nullable', 'boolean'],
            'observacoes'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cnpj.size'            => 'O CNPJ deve ter 14 dígitos.',
            'cnpj.unique'          => 'Já existe um prestador cadastrado com este CNPJ.',
            'tel1.digits_between'  => 'Telefone deve ter 10 dígitos (fixo) ou 11 (celular), com DDD.',
            'tel2.digits_between'  => 'Telefone deve ter 10 dígitos (fixo) ou 11 (celular), com DDD.',
            'cep.digits'           => 'O CEP deve ter 8 dígitos.',
            'uf.in'                => 'UF inválida.',
        ];
    }

    final protected static function cleanDigits(mixed $value): ?string
    {
        return $value === null ? null : Documento::digitos((string) $value);
    }
}

class StorePrestadorRequest extends AbstractPrestadorRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tdap.prestadores.create') ?? false;
    }

    protected function cnpjUniqueRule(): Unique
    {
        return Rule::unique('tdap_prestadores', 'cnpj')->whereNull('deleted_at');
    }
}

class UpdatePrestadorRequest extends AbstractPrestadorRequest
{
    use ResolveIdDaRota;

    public function authorize(): bool
    {
        return $this->user()?->can('tdap.prestadores.edit') ?? false;
    }

    protected function cnpjUniqueRule(): Unique
    {
        return Rule::unique('tdap_prestadores', 'cnpj')
            ->ignore($this->idDaRota('prestador'))
            ->whereNull('deleted_at');
    }
}
