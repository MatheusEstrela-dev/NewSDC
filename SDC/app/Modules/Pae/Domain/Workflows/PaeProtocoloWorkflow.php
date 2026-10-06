<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Workflows;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Models\User;
use App\Modules\Pae\Domain\Contracts\GuardaTransicaoPae;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Events\ParecerConcluidoV1;
use App\Modules\Pae\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTramitacao;
use App\Modules\Pae\Support\CicloProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use App\Modules\Pae\Services\PaeComunicacaoService;
use Illuminate\Support\Facades\DB;

/**
 * Maquina de estados do protocolo PAE (padrao Demandas\Domain\Workflows).
 *
 * Unico ponto que grava status: valida pelo enum, roda os guards e registra
 * tramitacao e timeline na mesma transacao. Sem estado proprio (os guards sao
 * stateless e a origem viaja em ContextoTransicao), seguro como singleton no Octane.
 */
final class PaeProtocoloWorkflow
{
    /**
     * Validacao para CCPAE: atalho de conclusao de protocolos que ja tem
     * certificado, a partir de qualquer estado ativo. Antes era um if solto
     * em PaeProtocoloService::changeStatus.
     */
    private const ORIGENS_VALIDACAO_CCPAE = [
        PaeProtocoloStatus::NOVO,
        PaeProtocoloStatus::ENTRADA_PROCESSO,
        PaeProtocoloStatus::CRIACAO_SDC,
        PaeProtocoloStatus::GERENCIAMENTO,
        PaeProtocoloStatus::NOTIFICACAO,
        PaeProtocoloStatus::ANALISE,
        PaeProtocoloStatus::APROVADO,
        PaeProtocoloStatus::ESPERAR_TRATATIVA,
        PaeProtocoloStatus::DILACAO,
    ];

    /**
     * @param  iterable<GuardaTransicaoPae>  $guardas
     */
    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly PaeComunicacaoService $comunicacoes,
        private readonly iterable $guardas,
    ) {}

    public static function transicaoPermitida(PaeProtocoloStatus $de, PaeProtocoloStatus $para): bool
    {
        return $de->canTransitionTo($para)
            || ($para === PaeProtocoloStatus::CCPAE && in_array($de, self::ORIGENS_VALIDACAO_CCPAE, true));
    }

    public function transitar(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $novo,
        User $user,
        string $obs = '',
        ?ContextoTransicao $contexto = null,
    ): PaeProtocolo {
        $contexto ??= ContextoTransicao::manual();

        return DB::transaction(function () use ($protocolo, $novo, $user, $obs, $contexto): PaeProtocolo {
            $protocolo = PaeProtocolo::query()->lockForUpdate()->findOrFail($protocolo->getKey());
            $anterior = $protocolo->status;

            if ($anterior === $novo) {
                return $protocolo;
            }
            if (! self::transicaoPermitida($anterior, $novo)) {
                throw TransicaoProibidaException::entre($anterior->getLabel(), $novo->getLabel());
            }
            foreach ($this->guardas as $guarda) {
                $guarda->check($protocolo, $novo, $contexto);
            }

            $desarquivar = (bool) $protocolo->arquivado && $novo === PaeProtocoloStatus::CCPAE;
            $protocolo->update(array_merge(
                ['status' => $novo->value, 'updated_by' => $user->id],
                $desarquivar ? ['arquivado' => false] : [],
            ));

            $tramitacao = PaeTramitacao::create([
                'protocolo_id' => $protocolo->id,
                'user_id' => $user->id,
                'status' => $novo->value,
                'obs' => $obs ?: null,
            ]);

            $descricao = "Status alterado de '{$anterior->getLabel()}' para '{$novo->getLabel()}'. {$obs}";
            if ($desarquivar) {
                $descricao .= ' Protocolo desarquivado automaticamente pela transicao para CCPAE.';
            }
            TimelinePae::registrar($protocolo, 'status_alterado', $descricao, $user);

            if ($anterior === PaeProtocoloStatus::ANALISE && $novo === PaeProtocoloStatus::REPROVADO) {
                $this->comunicacoes->abrir($protocolo, 'reprovacao_analise', $tramitacao->id, $obs);
            }

            if ($anterior === PaeProtocoloStatus::ANALISE
                && in_array($novo, [PaeProtocoloStatus::APROVADO, PaeProtocoloStatus::REPROVADO], true)) {
                $this->publicarParecerConcluido($protocolo, $anterior, $novo, $user, $tramitacao);
            }

            return $protocolo->fresh();
        });
    }

    /**
     * Leva o protocolo ate o alvo pelo menor caminho da maquina, numa transacao
     * so: um guard barrando no meio desfaz todos os passos.
     */
    public function conduzirAte(PaeProtocolo $protocolo, PaeProtocoloStatus $alvo, User $user, string $obs = ''): PaeProtocolo
    {
        return DB::transaction(function () use ($protocolo, $alvo, $user, $obs): PaeProtocolo {
            $atual = PaeProtocolo::query()->lockForUpdate()->findOrFail($protocolo->getKey());
            foreach (self::caminho($atual->status, $alvo) as $passo) {
                $atual = $this->transitar($atual, $passo, $user, $obs);
            }

            return $atual;
        });
    }

    /**
     * Busca em largura sobre getAllowedTransitions(). 14 estados: custo nulo.
     *
     * @return list<PaeProtocoloStatus>
     */
    public static function caminho(PaeProtocoloStatus $de, PaeProtocoloStatus $para): array
    {
        if ($de === $para) {
            return [];
        }

        $fila = [[$de, []]];
        $visitados = [$de->value => true];
        while ($fila !== []) {
            [$status, $trilha] = array_shift($fila);
            foreach ($status->getAllowedTransitions() as $proximo) {
                if (isset($visitados[$proximo->value])) {
                    continue;
                }
                $novaTrilha = [...$trilha, $proximo];
                if ($proximo === $para) {
                    return $novaTrilha;
                }
                $visitados[$proximo->value] = true;
                $fila[] = [$proximo, $novaTrilha];
            }
        }

        throw TransicaoProibidaException::entre($de->getLabel(), $para->getLabel());
    }

    /**
     * ParecerConcluidoV1 no outbox, na MESMA transacao da transicao.
     *
     * A saida de ANALISE para APROVADO/REPROVADO e o unico ponto em que a
     * analise se fecha com decisao. O creditado e o proprio analista que emitiu
     * o parecer; validador_user_id nao entra. Entrega pelo dt_status da
     * tramitacao recem-gravada (default do banco, por isso o fresh) e prazo
     * por limite_analise, agora calculado pelo PaePrazoService.
     */
    private function publicarParecerConcluido(
        PaeProtocolo $protocolo,
        PaeProtocoloStatus $statusAnterior,
        PaeProtocoloStatus $novo,
        User $user,
        PaeTramitacao $tramitacao,
    ): void {
        $this->outbox->persist(new ParecerConcluidoV1(
            eventId: DomainEvent::newId(),
            aggregateType: 'pae_protocolo',
            aggregateId: (string) $protocolo->id,
            occurredAt: new \DateTimeImmutable(),
            metadata: [
                'protocolo_id' => (int) $protocolo->id,
                'num_protocolo' => $protocolo->num_protocolo,
                'ciclo' => CicloProtocolo::de($protocolo->num_protocolo),
                'decisao' => $novo->value,
                'status_anterior' => $statusAnterior->value,
                'actor_user_id' => (int) $user->id,
                'credited_user_id' => (int) $user->id,
                'prazo_em' => $protocolo->limite_analise?->toDateString(),
                'entregue_em' => $tramitacao->fresh()?->dt_status?->toIso8601String(),
            ],
        ));
    }
}
