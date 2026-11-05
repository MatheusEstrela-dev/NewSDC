<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Remove do sdc os schemas de geo e treinamento que passaram a viver nos
 * bancos dedicados.
 *
 * Nunca derruba o deploy: se a chave do dominio ainda esta desligada ou a
 * verificacao origem x destino nao bate, so avisa. Nesse caso rode depois da
 * virada: php artisan db:remover-schemas-migrados
 */
return new class extends Migration
{
    private const DOMINIOS = ['geo', 'treinamento'];

    public function up(): void
    {
        foreach (self::DOMINIOS as $dominio) {
            try {
                $codigo = Artisan::call('db:remover-schemas-migrados', ['--dominio' => [$dominio]]);
                $saida = trim(Artisan::output());
            } catch (Throwable $e) {
                $codigo = 1;
                $saida = $e->getMessage();
            }

            if ($codigo === 0) {
                Log::info("Schemas de {$dominio} removidos do sdc.", ['saida' => $saida]);

                continue;
            }

            $aviso = "Schemas de {$dominio} mantidos no sdc. Rode depois da virada: php artisan db:remover-schemas-migrados --dominio={$dominio}";
            Log::warning($aviso, ['saida' => $saida]);
            fwrite(STDERR, $aviso.PHP_EOL);
        }
    }

    /**
     * Irreversivel: os dados vivem nos bancos dedicados, nada e recriado aqui.
     */
    public function down(): void {}
};
