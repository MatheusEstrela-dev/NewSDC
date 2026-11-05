<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Modules\PowerBi\Exceptions\FonteNaoEhStandbyException;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Support\Carbon;

/**
 * Janela segura de leitura da fonte: limite superior = min(agora, replay da
 * fonte) - margem; limite inferior = cursor - margem. A margem cobre commits
 * tardios; reler e inofensivo porque a gravacao compara hash.
 */
final class JanelaDaFonte
{
    public function __construct(
        public readonly Carbon $superior,
        public readonly Carbon $dadosAte,
        public readonly bool $emRecovery,
        public readonly int $margemSegundos,
    ) {
    }

    /** Consulta o relogio e o replay na conexao da fonte e aplica a trava de standby. */
    public static function obter(?int $margemSegundos = null, ?bool $exigeStandby = null): self
    {
        $margem = $margemSegundos ?? (int) config('powerbi.margem_segundos', 300);
        $exige = $exigeStandby ?? (bool) config('powerbi.fonte_exige_standby', false);

        $linha = ConexaoPowerBi::fonte()->selectOne(
            'SELECT pg_is_in_recovery() AS recovery, pg_last_xact_replay_timestamp() AS replay, now() AS agora'
        );

        $recovery = (bool) $linha->recovery;
        if ($exige && ! $recovery) {
            throw FonteNaoEhStandbyException::nova();
        }

        $agora = Carbon::parse($linha->agora);
        $dadosAte = ($recovery && $linha->replay !== null) ? Carbon::parse($linha->replay)->min($agora) : $agora;

        return new self($dadosAte->copy()->subSeconds($margem), $dadosAte, $recovery, $margem);
    }

    /** Limite inferior para um cursor (null = sem limite, leitura desde o inicio). */
    public function inferior(?EstadoCursor $estado): ?Carbon
    {
        return $estado?->atualizadoEm?->copy()->subSeconds($this->margemSegundos);
    }
}
