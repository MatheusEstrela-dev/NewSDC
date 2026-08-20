<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fecha a brecha aberta ao desligar o teto global do Sanctum.
 *
 * Antes, config('sanctum.expiration') valia 7 dias e vencia o expires_at de
 * cada token: os tokens emitidos como "sem expiracao" morriam em 7 dias mesmo
 * assim. Com o teto desligado, esses mesmos tokens passariam a viver para
 * sempre -- o oposto do que se quer.
 *
 * Esta carga da a eles um prazo concreto de 7 dias, que e exatamente o que o
 * teto lhes concedia. Nenhum token perde validade que ja tinha; nenhum ganha
 * vida eterna. Depois disto a tela nao emite mais token sem prazo.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addDays(7)]);
    }

    public function down(): void
    {
        // Sem volta: devolver expires_at para nulo com o teto desligado
        // transformaria os tokens em eternos, que e a falha que esta migration
        // existe para evitar.
    }
};
