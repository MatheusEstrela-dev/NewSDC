<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests\Api;

use App\Modules\Cisterna\Services\BeneficiarioService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base dos filtros de listagem da API do Cisterna.
 *
 * Filtro fora do dominio devolve 422 em vez de ser descartado: o consumidor
 * que escreve `etapa=fornecedo` precisa saber que errou, e nao receber a base
 * inteira achando que filtrou.
 */
abstract class FiltroApiRequest extends FormRequest
{
    /**
     * A autorizacao e do middleware `can:` na rota e da policy no `show`.
     * Repetir aqui daria 403 antes da validacao e mascararia qual das duas
     * barreiras recusou.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtros multivalor aceitos como lista. Declarados pela subclasse.
     *
     * @return array<int, string>
     */
    protected function camposMultivalor(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function regrasDoFiltro(): array;

    /**
     * @return array<string, mixed>
     */
    final public function rules(): array
    {
        return array_merge([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.BeneficiarioService::PORTE_MAXIMO_PAGINA],
        ], $this->regrasDoFiltro());
    }

    /**
     * Aceita `?situacao_analise=aprovado`, `?situacao_analise=a,b` e
     * `?situacao_analise[]=a&situacao_analise[]=b`, normalizando as tres para
     * array. Sem isso a regra `campo.*` nao se aplica ao escalar e o valor
     * entraria sem validacao.
     */
    protected function prepareForValidation(): void
    {
        foreach ($this->camposMultivalor() as $campo) {
            if (! $this->has($campo)) {
                continue;
            }

            $valor = $this->input($campo);

            if (is_string($valor)) {
                $valor = array_values(array_filter(
                    array_map('trim', explode(',', $valor)),
                    fn (string $v): bool => $v !== ''
                ));
            }

            $this->merge([$campo => is_array($valor) ? array_values($valor) : [$valor]]);
        }
    }

    public function porPagina(): int
    {
        return $this->integer('per_page', BeneficiarioService::PORTE_PADRAO_PAGINA);
    }

    /**
     * @return array<string, mixed>
     */
    public function filtros(): array
    {
        return $this->safe()->except(['page', 'per_page']);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.max' => 'O maximo por pagina e '.BeneficiarioService::PORTE_MAXIMO_PAGINA.'.',
        ];
    }
}
