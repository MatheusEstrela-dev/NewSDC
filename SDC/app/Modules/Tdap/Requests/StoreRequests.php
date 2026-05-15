<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/*
|--------------------------------------------------------------------------
| TDAP — Store Requests (arquivo consolidado)
|--------------------------------------------------------------------------
|
| Todas as classes Store*Request do modulo TDAP. Cada classe combina:
|   - extends StoreTdapRequest    (autorize 'tdap.<resource>.create')
|   - use   <Entidade>RulesTrait  (regras de validacao)
|   - override numeroUniqueRule/placaUniqueRule/cnpjUniqueRule/ignoreId
|     (regra de unicidade SEM ignore, pois e Store)
|
| Bases abstratas (StoreTdapRequest) + traits ficam em TdapRequest.php.
*/

/**
 * Cria Ata de Registro de Precos.
 */
class StoreAtaRequest extends StoreTdapRequest
{
    use AtaRulesTrait;

    public function tdapResource(): string
    {
        return 'atas';
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_atas', 'numero')->whereNull('deleted_at');
    }
}

/**
 * Cria Caminhao-tanque vinculado a um Prestador.
 */
class StoreCaminhaoRequest extends StoreTdapRequest
{
    use CaminhaoRulesTrait;

    public function tdapResource(): string
    {
        return 'caminhoes';
    }

    protected function placaUniqueRule(): Unique
    {
        return Rule::unique('tdap_caminhoes', 'placa')->whereNull('deleted_at');
    }
}

/**
 * Aloca um Caminhao em um Cronograma.
 * Reutiliza permissao 'tdap.cronogramas.edit' (alocacao = edicao do
 * cronograma-pai, nao acao propria do CronoCaminhao).
 */
class StoreCronoCaminhaoRequest extends StoreTdapRequest
{
    use CronoCaminhaoRulesTrait;

    public function tdapResource(): string
    {
        return 'cronogramas';
    }

    public function tdapAction(): string
    {
        return 'edit';
    }

    protected function includeVinculo(): bool
    {
        return true;
    }
}

/**
 * Cria Cronograma de fornecimento.
 */
class StoreCronogramaRequest extends StoreTdapRequest
{
    use CronogramaRulesTrait;

    public function tdapResource(): string
    {
        return 'cronogramas';
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_cronogramas', 'numero')->whereNull('deleted_at');
    }
}

/**
 * Registra uma Viagem realizada por um CronoCaminhao.
 */
class StoreCronoViagemRequest extends StoreTdapRequest
{
    public function tdapResource(): string
    {
        return 'viagens';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'crono_caminhao_id' => ['required', 'integer', Rule::exists('tdap_crono_caminhoes', 'id')->whereNull('deleted_at')],
            'data_registro'     => ['required', 'date'],
            'obs'               => ['nullable', 'string', 'max:1000'],
        ];
    }
}

/**
 * Cria Lote vinculado a Ata + Municipio + Prestador.
 */
class StoreLoteRequest extends StoreTdapRequest
{
    use LoteRulesTrait;

    public function tdapResource(): string
    {
        return 'lotes';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}

/**
 * Cria Prestador (empresa contratada para fornecimento).
 */
class StorePrestadorRequest extends StoreTdapRequest
{
    use PrestadorRulesTrait;

    public function tdapResource(): string
    {
        return 'prestadores';
    }

    protected function cnpjUniqueRule(): Unique
    {
        return Rule::unique('tdap_prestadores', 'cnpj')->whereNull('deleted_at');
    }
}

/**
 * Abre um Processo TDAP (agregado raiz do workflow Fase 6).
 */
class StoreProcessoTdapRequest extends StoreTdapRequest
{
    use ProcessoTdapRulesTrait;

    public function tdapResource(): string
    {
        return 'processos';
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_processos', 'numero')->whereNull('deleted_at');
    }
}

/**
 * Cria Vistoria de Caminhao (27 itens estruturais + 7 itens de tanque).
 */
class StoreVistoriaRequest extends StoreTdapRequest
{
    use VistoriaRulesTrait;

    public function tdapResource(): string
    {
        return 'vistorias';
    }
}
