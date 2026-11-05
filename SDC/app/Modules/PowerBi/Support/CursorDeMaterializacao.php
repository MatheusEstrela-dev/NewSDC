<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Cursores de um modulo em controle.cursores. Escreve na mesma conexao do
 * GravadorBi: chamado dentro de emTransacao(), o cursor so avanca se a
 * gravacao for confirmada.
 */
final class CursorDeMaterializacao
{
    public function __construct(
        public readonly string $modulo,
        private readonly ?Carbon $desde = null,
    ) {
    }

    /** Estado salvo; com --desde, o cursor e forcado para essa data (sem id). */
    public function estado(string $tabela): EstadoCursor
    {
        if ($this->desde !== null) {
            return new EstadoCursor($this->desde, null);
        }

        $linha = ConexaoPowerBi::escrita()->table('controle.cursores')
            ->where('modulo', $this->modulo)->where('tabela', $tabela)->first();

        return new EstadoCursor(
            $linha?->ultimo_atualizado_em !== null ? Carbon::parse($linha->ultimo_atualizado_em) : null,
            $linha?->ultimo_id !== null ? (string) $linha->ultimo_id : null,
        );
    }

    /** Avanca o cursor. Para estrategia completa passe null em atualizadoEm/id. */
    public function avancar(string $tabela, ?Carbon $atualizadoEm, ?string $id, JanelaDaFonte $janela): void
    {
        ConexaoPowerBi::escrita()->statement(
            'INSERT INTO controle.cursores (modulo, tabela, ultimo_atualizado_em, ultimo_id, fonte_replay_em, atualizado_em)
             VALUES (?, ?, ?, ?, ?, now())
             ON CONFLICT (modulo, tabela) DO UPDATE SET
                ultimo_atualizado_em = COALESCE(EXCLUDED.ultimo_atualizado_em, controle.cursores.ultimo_atualizado_em),
                ultimo_id = CASE WHEN EXCLUDED.ultimo_atualizado_em IS NULL THEN controle.cursores.ultimo_id ELSE EXCLUDED.ultimo_id END,
                fonte_replay_em = EXCLUDED.fonte_replay_em,
                atualizado_em = now()',
            [$this->modulo, $tabela, $atualizadoEm?->toIso8601String(), $id, $janela->superior->toIso8601String()],
        );
    }

    /** Executa o bloco numa transacao do sdc_powerbi (gravacao + cursor atomicos). */
    public function emTransacao(Closure $fn): mixed
    {
        return ConexaoPowerBi::escrita()->transaction($fn);
    }
}
