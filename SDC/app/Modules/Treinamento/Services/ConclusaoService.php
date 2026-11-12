<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\ResultadoConclusao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\CriteriosDeConclusao;

/**
 * Avalia o criterio do tipo do treinamento e congela o resultado na inscricao
 * (concluido_em + aproveitamento, spec 8.7). Gravado uma unica vez, sob lock
 * da linha: eventos repetidos ou concorrentes nao regravam nem divergem.
 */
class ConclusaoService
{
    public function __construct(private readonly CriteriosDeConclusao $criterios) {}

    /**
     * Resultado congelado, ou null enquanto a inscricao nao cumpre o criterio.
     */
    public function registrar(Inscricao $inscricao): ?ResultadoConclusao
    {
        return ConexaoTreinamento::transacao(function () use ($inscricao) {
            $travada = Inscricao::query()->with('treinamento')->lockForUpdate()->findOrFail($inscricao->id);

            $resultado = $this->criterios->para($travada->treinamento)->avaliar($travada);
            if ($resultado === null) {
                return null;
            }

            $gravadaAgora = false;
            if ($travada->concluido_em === null) {
                // Update direto: nao dispara a trilha de acoes do model nem passa
                // pelo $fillable (a conclusao so se escreve aqui).
                $gravadaAgora = Inscricao::query()->whereKey($travada->id)->whereNull('concluido_em')->update([
                    'concluido_em' => $resultado->concluidoEm,
                    'aproveitamento' => $resultado->aproveitamento,
                ]) === 1;
                $travada->refresh();
            }

            $inscricao->forceFill([
                'concluido_em' => $travada->concluido_em,
                'aproveitamento' => $travada->aproveitamento,
            ])->syncOriginalAttributes(['concluido_em', 'aproveitamento']);

            return new ResultadoConclusao($travada->concluido_em, $travada->aproveitamento, $gravadaAgora);
        });
    }
}
