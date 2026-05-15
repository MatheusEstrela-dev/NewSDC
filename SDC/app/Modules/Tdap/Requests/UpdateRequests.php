<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Requests;

use App\Modules\Tdap\Enums\EstadoProcesso;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

/*
|--------------------------------------------------------------------------
| TDAP — Update Requests + Acoes especiais (arquivo consolidado)
|--------------------------------------------------------------------------
|
| Reune:
|  - Todas as classes Update*Request (extends UpdateTdapRequest + trait)
|  - Acoes especiais (acao != 'create'/'edit'):
|      TransitarProcessoTdapRequest  (action = 'transitar')
|      ValidarCronoViagemRequest     (action = 'validar')
|
| Bases (UpdateTdapRequest, TdapRequest) e traits estao em TdapRequest.php.
*/

/**
 * Edita Ata existente; mantem unique de numero ignorando o ID atual.
 */
class UpdateAtaRequest extends UpdateTdapRequest
{
    use AtaRulesTrait;

    public function tdapResource(): string
    {
        return 'atas';
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_atas', 'numero')
            ->ignore($this->resourceId())
            ->whereNull('deleted_at');
    }
}

/**
 * Edita Caminhao existente.
 */
class UpdateCaminhaoRequest extends UpdateTdapRequest
{
    use CaminhaoRulesTrait;

    public function tdapResource(): string
    {
        return 'caminhoes';
    }

    protected function routeParameterName(): string
    {
        return 'caminhao';
    }

    protected function placaUniqueRule(): Unique
    {
        return Rule::unique('tdap_caminhoes', 'placa')
            ->ignore($this->resourceId())
            ->whereNull('deleted_at');
    }
}

/**
 * Edita CronoCaminhao existente (sem alterar chave da alocacao).
 * Permissao = 'tdap.cronogramas.edit'.
 */
class UpdateCronoCaminhaoRequest extends UpdateTdapRequest
{
    use CronoCaminhaoRulesTrait;

    public function tdapResource(): string
    {
        return 'cronogramas';
    }

    protected function routeParameterName(): string
    {
        return 'cronoCaminhao';
    }

    protected function includeVinculo(): bool
    {
        return false;
    }
}

/**
 * Edita Cronograma existente.
 */
class UpdateCronogramaRequest extends UpdateTdapRequest
{
    use CronogramaRulesTrait;

    public function tdapResource(): string
    {
        return 'cronogramas';
    }

    protected function numeroUniqueRule(): Unique
    {
        return Rule::unique('tdap_cronogramas', 'numero')
            ->ignore($this->resourceId())
            ->whereNull('deleted_at');
    }
}

/**
 * Edita Lote existente.
 */
class UpdateLoteRequest extends UpdateTdapRequest
{
    use LoteRulesTrait;

    public function tdapResource(): string
    {
        return 'lotes';
    }

    protected function ignoreId(): ?int
    {
        return $this->resourceId();
    }
}

/**
 * Edita Prestador existente.
 */
class UpdatePrestadorRequest extends UpdateTdapRequest
{
    use PrestadorRulesTrait;

    public function tdapResource(): string
    {
        return 'prestadores';
    }

    protected function cnpjUniqueRule(): Unique
    {
        return Rule::unique('tdap_prestadores', 'cnpj')
            ->ignore($this->resourceId())
            ->whereNull('deleted_at');
    }
}

/**
 * Edita Vistoria existente.
 */
class UpdateVistoriaRequest extends UpdateTdapRequest
{
    use VistoriaRulesTrait;

    public function tdapResource(): string
    {
        return 'vistorias';
    }
}

// =========================================================================
// Acoes especiais (acao != 'create' / 'edit')
// =========================================================================

/**
 * Transita um Processo TDAP entre estados do workflow.
 * Permissao: tdap.processos.transitar.
 */
class TransitarProcessoTdapRequest extends TdapRequest
{
    public function tdapResource(): string
    {
        return 'processos';
    }

    public function tdapAction(): string
    {
        return 'transitar';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado_alvo' => ['required', new Enum(EstadoProcesso::class)],
            'motivo'      => ['nullable', 'string', 'max:2000'],
        ];
    }
}

/**
 * Valida (aprovar/rejeitar) uma Viagem registrada.
 * Permissao: tdap.viagens.validar.
 */
class ValidarCronoViagemRequest extends TdapRequest
{
    public function tdapResource(): string
    {
        return 'viagens';
    }

    public function tdapAction(): string
    {
        return 'validar';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'aprovada'      => ['required', 'boolean'],
            'obs_aprovacao' => ['nullable', 'string', 'max:1000', 'required_if:aprovada,false'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'obs_aprovacao.required_if' => 'Ao rejeitar a viagem, informe o motivo.',
        ];
    }
}
