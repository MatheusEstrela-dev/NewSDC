<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Exceptions\TransicaoProibidaException;
use App\Modules\Demandas\Domain\Workflows\DemandaWorkflow;
use App\Modules\Demandas\DTOs\ResolucaoDemandaData;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use Illuminate\Support\Facades\DB;

/**
 * Mudancas de status pedidas pela tela. Cada metodo e uma acao do usuario e
 * grava UMA linha de historico, por mais passos que o workflow percorra.
 */
final class DemandaStatusService
{
    public function __construct(
        private readonly DemandaWorkflow $workflow,
        private readonly HistoricoDemanda $historico,
    ) {}

    public function alterarStatus(Demanda $demanda, StatusDemanda $alvo, int $userId): Demanda
    {
        if ($alvo === StatusDemanda::RESOLVIDA) {
            throw new TransicaoProibidaException('Use "Resolver chamado" para concluir, informando as datas.');
        }

        return DB::transaction(function () use ($demanda, $alvo, $userId): Demanda {
            if ($alvo === StatusDemanda::EM_PROGRESSO) {
                $demanda = $this->assumirSeSemResponsavel($demanda, $userId);
            }
            $resultado = $this->workflow->conduzirAte($demanda, $alvo, $userId);
            $this->historico->registrar(
                $resultado, $userId, AcaoHistoricoDemanda::STATUS_ALTERADO,
                'Status alterado manualmente para: '.$alvo->etapa()->label().'.',
                campo: 'status', novo: $alvo->value,
            );

            return $resultado;
        });
    }

    public function resolver(Demanda $demanda, ResolucaoDemandaData $dados, int $userId): Demanda
    {
        return DB::transaction(function () use ($demanda, $dados, $userId): Demanda {
            $demanda = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());

            if (! $demanda->status->isActive()) {
                throw new TransicaoProibidaException('Esta demanda já está concluída.');
            }

            $aberturaAnterior = $demanda->created_at;
            // O input datetime-local trunca os segundos; comparar por minuto evita
            // marcar "ajustada" (e regravar created_at) quando so os segundos diferem.
            $mudouMinuto = ! $aberturaAnterior->copy()->startOfMinute()->equalTo($dados->abertaEm->startOfMinute());
            if ($mudouMinuto) {
                $demanda->created_at = $dados->abertaEm;
                $demanda->saveQuietly();
                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::EDITADA, 'Data de abertura ajustada na resolução.',
                    campo: 'created_at', anterior: $aberturaAnterior->toIso8601String(), novo: $dados->abertaEm->toIso8601String(),
                );
            }

            $demanda = $this->assumirSeSemResponsavel($demanda, $userId);
            $resolvida = $this->workflow->conduzirAte($demanda, StatusDemanda::RESOLVIDA, $userId, $dados->resolvidaEm);
            $this->historico->registrar(
                $resolvida, $userId, AcaoHistoricoDemanda::RESOLVIDA,
                'Chamado resolvido em '.$dados->resolvidaEm->format('d/m/Y H:i').'.',
            );

            return $resolvida;
        });
    }

    public function reabrir(Demanda $demanda, int $userId): Demanda
    {
        return DB::transaction(function () use ($demanda, $userId): Demanda {
            $demanda = Demanda::query()->lockForUpdate()->findOrFail($demanda->getKey());

            if ($demanda->status !== StatusDemanda::RESOLVIDA) {
                throw new TransicaoProibidaException('Só é possível reabrir uma demanda concluída.');
            }

            $reaberta = $this->workflow->transitar($demanda, StatusDemanda::EM_PROGRESSO, $userId);
            $this->historico->registrar(
                $reaberta, $userId, AcaoHistoricoDemanda::REABERTA,
                'Chamado REABERTO. Status alterado para: Em andamento.',
            );

            return $reaberta;
        });
    }

    public function assumirSeSemResponsavel(Demanda $demanda, int $userId): Demanda
    {
        if ($demanda->atribuido_para_id !== null) {
            return $demanda;
        }
        $demanda->atribuido_para_id = $userId;
        $demanda->save();
        $this->historico->registrar(
            $demanda, $userId, AcaoHistoricoDemanda::ATRIBUIDA, 'Responsável definido ao iniciar o atendimento.',
            campo: 'atribuido_para_id', novo: (string) $userId,
        );

        return $demanda;
    }
}
