<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Enums\ParecerVistoria;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

/*
|--------------------------------------------------------------------------
| TDAP Requests — Bases + Traits de regras
|--------------------------------------------------------------------------
|
| Concentra TODA a logica reaproveitavel das Form Requests do modulo TDAP
| em 3 camadas claramente categorizadas:
|
|   1) BASES ABSTRATAS         (autorize + acao Spatie)
|        - TdapRequest          autorize generico: tdap.<resource>.<action>
|        - StoreTdapRequest     acao = 'create'
|        - UpdateTdapRequest    acao = 'edit', resourceId via rota
|
|   2) TRAITS DE REGRAS         (validacao por entidade — DRY entre Store/Update)
|        - AtaRulesTrait
|        - CaminhaoRulesTrait
|        - CronoCaminhaoRulesTrait
|        - CronogramaRulesTrait
|        - LoteRulesTrait
|        - PrestadorRulesTrait
|        - ProcessoTdapRulesTrait
|        - VistoriaRulesTrait
|
| As classes concretas Store* e Update* vivem em StoreRequests.php e
| UpdateRequests.php — combinam estas bases + traits.
|
| Autoload via classmap (composer.json). Apos alterar:
|   docker exec newsdc_frankenphp_local composer dump-autoload -o
|
| SOLID:
|  - SRP: cada classe/trait tem responsabilidade unica e categorizada.
|  - OCP: novas entidades = novo trait + concreta sem tocar nas bases.
|  - LSP: subclasses sao substituiveis pelas bases.
|  - DIP: bases dependem das abstracoes tdapResource()/tdapAction().
*/

// =========================================================================
// 1) BASES ABSTRATAS
// =========================================================================

/**
 * Base unificada para todas as Form Requests do modulo TDAP.
 * Autorizacao via permissao Spatie tdap.<resource>.<action>.
 */
abstract class TdapRequest extends FormRequest
{
    /** Recurso TDAP em snake plural (ex.: 'atas', 'prestadores'). */
    abstract public function tdapResource(): string;

    /** Acao da permissao Spatie (ex.: 'create', 'edit', 'ativar'). */
    abstract public function tdapAction(): string;

    public function authorize(): bool
    {
        $permission = "tdap.{$this->tdapResource()}.{$this->tdapAction()}";

        return $this->user()?->can($permission) ?? false;
    }
}

/**
 * Base para todos os Store*Request (cria entidade).
 * Fixa acao 'create'.
 */
abstract class StoreTdapRequest extends TdapRequest
{
    public function tdapAction(): string
    {
        return 'create';
    }
}

/**
 * Base para todos os Update*Request (edita entidade).
 * Fixa acao 'edit' e expoe resourceId() via parametro de rota.
 */
abstract class UpdateTdapRequest extends TdapRequest
{
    public function tdapAction(): string
    {
        return 'edit';
    }

    public function resourceId(): ?int
    {
        $param = $this->routeParameterName();
        $value = $this->route($param);

        return is_numeric($value) ? (int) $value : null;
    }

    /** Singulariza o recurso para extrair o parametro de rota. */
    protected function routeParameterName(): string
    {
        return rtrim($this->tdapResource(), 's');
    }
}

// =========================================================================
// 2) TRAITS DE REGRAS POR ENTIDADE
// =========================================================================

/**
 * Regras compartilhadas entre Store e Update de Ata TDAP.
 */
trait AtaRulesTrait
{
    abstract protected function numeroUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        if ($this->input('numero')) {
            $this->merge(['numero' => mb_strtoupper(trim((string) $this->input('numero')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero'      => ['required', 'string', 'max:20', $this->numeroUniqueRule()],
            'dt_inicio'   => ['required', 'date'],
            'dt_final'    => ['required', 'date', 'after_or_equal:dt_inicio'],
            'historico'   => ['nullable', 'string', 'max:5000'],
            'ativo'       => ['nullable', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dt_final.after_or_equal' => 'Data final deve ser igual ou posterior à data inicial.',
        ];
    }
}

/**
 * Regras compartilhadas entre Store e Update de Caminhao TDAP.
 */
trait CaminhaoRulesTrait
{
    abstract protected function placaUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        $placa = $this->input('placa');
        $this->merge([
            'placa' => $placa
                ? mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $placa) ?? '')
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prestador_id'  => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'placa'         => ['required', 'string', 'min:7', 'max:8', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $this->placaUniqueRule()],
            'marca'         => ['nullable', 'string', 'max:50'],
            'modelo'        => ['nullable', 'string', 'max:50'],
            'cor'           => ['nullable', 'string', 'max:30'],
            'ano'           => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'capacidade_m3' => ['required', 'numeric', 'min:0.5', 'max:999.99'],
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
            'placa.regex' => 'Placa deve seguir o padrão Mercosul (AAA1A11) ou antigo (AAA1111).',
            'ano.regex'   => 'Ano deve ter 4 dígitos numéricos.',
        ];
    }
}

/**
 * Regras de alocacao de Caminhao em Cronograma.
 *  - Store: cronograma_id + caminhao_id obrigatorios.
 *  - Update: chave imutavel; apenas comunidade/agua/viagens/ordem mudam.
 */
trait CronoCaminhaoRulesTrait
{
    abstract protected function includeVinculo(): bool;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'comunidade_id' => ['nullable', 'integer'],
            'agua_prevista' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'num_viagens'   => ['required', 'integer', 'min:1', 'max:10000'],
            'ordem'         => ['nullable', 'integer', 'min:0', 'max:255'],
        ];

        if ($this->includeVinculo()) {
            $rules['cronograma_id'] = ['required', 'integer', Rule::exists('tdap_cronogramas', 'id')->whereNull('deleted_at')];
            $rules['caminhao_id']   = ['required', 'integer', Rule::exists('tdap_caminhoes', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}

/**
 * Regras compartilhadas entre Store e Update de Cronograma.
 * Inclui coerencia referencial Lote -> Ata/Municipio/Prestador.
 */
trait CronogramaRulesTrait
{
    abstract protected function numeroUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        if ($this->input('numero')) {
            $this->merge(['numero' => mb_strtoupper(trim((string) $this->input('numero')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero'                => ['required', 'string', 'max:20', $this->numeroUniqueRule()],
            'empenho'               => ['nullable', 'string', 'max:30'],
            'ata_id'                => ['required', 'integer', Rule::exists('tdap_atas', 'id')->whereNull('deleted_at')],
            'lote_id'               => ['required', 'integer', Rule::exists('tdap_lotes', 'id')->whereNull('deleted_at')],
            'municipio_id'          => ['required', 'integer', Rule::exists('municipios', 'id')],
            'prestador_id'          => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'cnpj'                  => ['nullable', 'string', 'max:18'],
            'consumo_diario'        => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'dias'                  => ['required', 'integer', 'min:1', 'max:1000'],
            'fator'                 => ['nullable', 'numeric', 'min:0.01', 'max:99.99'],
            'dt_inicio'             => ['required', 'date'],
            'dt_final'              => ['required', 'date', 'after_or_equal:dt_inicio'],
            'justificativa'         => ['nullable', 'string', 'max:5000'],
            'nota_empenho'          => ['nullable', 'string', 'max:50'],
            'ponto_captacao_id'     => ['nullable', 'integer'],
            'observacao'            => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $loteId = (int) $this->input('lote_id');
            if ($loteId === 0) {
                return;
            }

            $lote = Lote::find($loteId);
            if (! $lote) {
                return;
            }

            if ((int) $lote->ata_id !== (int) $this->input('ata_id')) {
                $v->errors()->add('lote_id', 'O Lote selecionado nao pertence a Ata escolhida.');
            }
            if ((int) $lote->municipio_id !== (int) $this->input('municipio_id')) {
                $v->errors()->add('municipio_id', 'O Municipio nao confere com o Lote escolhido.');
            }
            if ((int) $lote->prestador_id !== (int) $this->input('prestador_id')) {
                $v->errors()->add('prestador_id', 'O Prestador nao confere com o Lote escolhido.');
            }
        });
    }
}

/**
 * Regras compartilhadas entre Store e Update de Lote TDAP.
 * Inclui validacao composta (ata_id, municipio_id) unica.
 */
trait LoteRulesTrait
{
    abstract protected function ignoreId(): ?int;

    protected function prepareForValidation(): void
    {
        if ($this->input('numero')) {
            $this->merge(['numero' => mb_strtoupper(trim((string) $this->input('numero')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ata_id'       => ['required', 'integer', Rule::exists('tdap_atas', 'id')->whereNull('deleted_at')],
            'municipio_id' => ['required', 'integer', Rule::exists('municipios', 'id')],
            'prestador_id' => ['required', 'integer', Rule::exists('tdap_prestadores', 'id')->whereNull('deleted_at')],
            'numero'       => ['required', 'string', 'max:20'],
            'nome'         => ['nullable', 'string', 'max:150'],
            'qtd_agua_m3'  => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'valor_m3'     => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'ativo'        => ['nullable', 'boolean'],
            'observacoes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $ataId = (int) $this->input('ata_id');
            $municipioId = (int) $this->input('municipio_id');
            if ($ataId === 0 || $municipioId === 0) {
                return;
            }

            $query = Lote::query()
                ->where('ata_id', $ataId)
                ->where('municipio_id', $municipioId)
                ->whereNull('deleted_at');

            if ($id = $this->ignoreId()) {
                $query->where('id', '!=', $id);
            }

            if ($query->exists()) {
                $msg = $this->ignoreId()
                    ? 'Ja existe outro lote para esta Ata e Municipio.'
                    : 'Ja existe um lote para esta Ata e Municipio.';
                $v->errors()->add('municipio_id', $msg);
            }
        });
    }
}

/**
 * Regras compartilhadas entre Store e Update de Prestador TDAP.
 * Inclui sanitizacao de digits (CNPJ/tel/CEP).
 */
trait PrestadorRulesTrait
{
    abstract protected function cnpjUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj'  => self::cleanDigits($this->input('cnpj')),
            'tel1'  => self::cleanDigits($this->input('tel1')),
            'tel2'  => self::cleanDigits($this->input('tel2')),
            'cep'   => self::cleanDigits($this->input('cep')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'uf'    => $this->input('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cnpj'          => ['required', 'string', 'size:14', $this->cnpjUniqueRule()],
            'nome'          => ['required', 'string', 'max:150'],
            'representante' => ['nullable', 'string', 'max:150'],
            'email'         => ['required', 'email', 'max:150'],
            'tel1'          => ['nullable', 'string', 'max:20'],
            'tel2'          => ['nullable', 'string', 'max:20'],
            'endereco'      => ['nullable', 'string', 'max:200'],
            'bairro'        => ['nullable', 'string', 'max:100'],
            'cidade'        => ['nullable', 'string', 'max:100'],
            'uf'            => ['nullable', 'string', 'size:2'],
            'cep'           => ['nullable', 'string', 'max:8'],
            'ativo'         => ['nullable', 'boolean'],
            'observacoes'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    final protected static function cleanDigits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/\D+/', '', (string) $value) ?? '';

        return $clean === '' ? null : $clean;
    }
}

/**
 * Regras compartilhadas entre Store/Update de ProcessoTdap.
 */
trait ProcessoTdapRulesTrait
{
    abstract protected function numeroUniqueRule(): Unique;

    protected function prepareForValidation(): void
    {
        if ($this->input('numero')) {
            $this->merge(['numero' => mb_strtoupper(trim((string) $this->input('numero')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero'       => ['required', 'string', 'max:20', $this->numeroUniqueRule()],
            'municipio_id' => ['required', 'integer', Rule::exists('municipios', 'id')],
            'contexto'     => ['nullable', 'array'],
        ];
    }
}

/**
 * Regras compartilhadas entre Store e Update de Vistoria TDAP.
 * Inclui os 27 itens estruturais + 7 itens de tanque (booleans + obs).
 */
trait VistoriaRulesTrait
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nome'        => ['required', 'string', 'max:150'],
            'edital'      => ['nullable', 'string', 'max:50'],
            'lote'        => ['nullable', 'string', 'max:30'],
            'placa_id'    => ['required', 'integer', Rule::exists('tdap_caminhoes', 'id')->whereNull('deleted_at')],
            'modelo'      => ['nullable', 'string', 'max:50'],
            'cor'         => ['nullable', 'string', 'max:30'],
            'data'        => ['required', 'date'],
            'ano'         => ['nullable', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'capacidade'  => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'parecer'     => ['required', new Enum(ParecerVistoria::class)],
            'ficha'       => ['nullable', 'string', 'max:50'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (Vistoria::ITENS_ESTRUTURAIS as $campo) {
            $rules[$campo] = ['nullable', 'boolean'];
            $rules["{$campo}_obs"] = ['nullable', 'string', 'max:500'];
        }
        foreach (Vistoria::ITENS_TANQUE as $campo) {
            $rules[$campo] = ['nullable', 'boolean'];
            $rules["{$campo}_obs"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ano.regex' => 'Ano deve ter 4 dígitos numéricos.',
        ];
    }
}
