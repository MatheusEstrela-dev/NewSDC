<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Events\ComentarioAdicionadoV1;
use App\Modules\Demandas\Domain\Workflows\DemandaWorkflow;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaComentario;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DemandaInteractionService
{
    public function __construct(
        private readonly DemandaWorkflow $workflow,
        private readonly DemandaStatusService $status,
        private readonly HistoricoDemanda $historico,
    ) {}

    /**
     * Regra do legado: a primeira resposta publica de quem nao e o solicitante
     * tira a demanda de "Aberto". Aqui ela tambem assume o atendimento, porque o
     * dominio nao aceita demanda em andamento sem responsavel.
     */
    public function comentar(Demanda $demanda, int $autorId, string $conteudo, bool $interno): DemandaComentario
    {
        return DB::transaction(function () use ($demanda, $autorId, $conteudo, $interno): DemandaComentario {
            $comentario = $demanda->comments()->create([
                'user_id' => $autorId,
                'tipo' => 'comentario',
                'conteudo' => $conteudo,
                'interno' => $interno,
            ]);

            $respostaDeTerceiro = ! $interno && $autorId !== (int) $demanda->solicitante_id;
            if ($respostaDeTerceiro && $demanda->primeira_resposta_em === null) {
                $demanda->primeira_resposta_em = now();
                $demanda->save();
            }

            if ($respostaDeTerceiro && $demanda->status === StatusDemanda::ABERTA) {
                $demanda = $this->status->assumirSeSemResponsavel($demanda, $autorId);
                $demanda = $this->workflow->conduzirAte($demanda, StatusDemanda::EM_PROGRESSO, $autorId);
                $this->historico->registrar(
                    $demanda, $autorId, AcaoHistoricoDemanda::STATUS_ALTERADO,
                    'Alteração de Status Automática: primeiro comentário levou a demanda para Em andamento.',
                    ['automatico' => true], campo: 'status', novo: StatusDemanda::EM_PROGRESSO->value,
                );
            }

            DB::afterCommit(static fn () => event(ComentarioAdicionadoV1::create(
                $demanda->id,
                $comentario->id,
                $autorId,
                $interno,
            )));

            return $comentario;
        });
    }

    public function atribuir(Demanda $demanda, int $responsavelId, int $autorId): void
    {
        $anterior = $demanda->atribuido_para_id;
        if ($anterior !== null && (int) $anterior === $responsavelId) {
            return;
        }

        DB::transaction(function () use ($demanda, $responsavelId, $autorId, $anterior): void {
            $demanda->atribuido_para_id = $responsavelId;
            $demanda->save();

            $nomes = User::query()->whereIn('id', array_filter([$anterior, $responsavelId]))->pluck('name', 'id');
            $this->historico->registrar(
                $demanda, $autorId, AcaoHistoricoDemanda::ATRIBUIDA,
                sprintf('Responsável alterado de %s para %s.', $nomes[$anterior] ?? 'ninguém', $nomes[$responsavelId] ?? '#'.$responsavelId),
                campo: 'atribuido_para_id', anterior: $anterior === null ? null : (string) $anterior, novo: (string) $responsavelId,
            );
        });
    }
}
