<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Events\ComentarioAdicionadoV1;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaComentario;
use Illuminate\Support\Facades\DB;

final class DemandaInteractionService
{
    public function comentar(Demanda $demanda, int $autorId, string $conteudo, bool $interno): DemandaComentario
    {
        return DB::transaction(function () use ($demanda, $autorId, $conteudo, $interno): DemandaComentario {
            $comentario = $demanda->comments()->create([
                'user_id' => $autorId,
                'tipo' => 'comentario',
                'conteudo' => $conteudo,
                'interno' => $interno,
            ]);

            if (! $interno && $autorId !== (int) $demanda->solicitante_id && $demanda->primeira_resposta_em === null) {
                $demanda->primeira_resposta_em = now();
                $demanda->save();
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

    public function atribuir(Demanda $demanda, int $responsavelId): void
    {
        $demanda->atribuido_para_id = $responsavelId;
        $demanda->save();
    }
}
