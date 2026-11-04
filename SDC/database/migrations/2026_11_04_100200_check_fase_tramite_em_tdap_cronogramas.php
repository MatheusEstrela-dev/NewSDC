<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CHECK no dominio de `tdap_cronogramas.fase_tramite` (enum FaseCronograma).
 *
 * Nao conflita com o trigger de 2026_10_29_120100: ele e BEFORE INSERT/UPDATE,
 * entao corrige a fase antes de o CHECK ser avaliado, e todos os ramos dele
 * terminam num valor do dominio. O CHECK cobre o que o trigger nao ve: escrita
 * com trigger desligado (pg_restore/COPY com session_replication_role =
 * replica, ALTER TABLE ... DISABLE TRIGGER) e um valor novo no enum PHP sem
 * migration correspondente.
 *
 * Os dados existentes cabem: 2026_10_29_120100 passou o acervo inteiro pelo
 * trigger (UPDATE ... SET fase_tramite = fase_tramite). Mesmo assim, por
 * seguranca, a constraint entra NOT VALID (vale ja para escritas novas), as
 * linhas fora do dominio -- se houver alguma, vinda de restore -- sao
 * normalizadas pelo proprio trigger, e so entao o VALIDATE roda. Tudo dentro
 * da transacao da migration, entao NOT VALID aqui nao reduz lock; a tabela e
 * pequena e o ganho seria nulo.
 *
 * Valor novo em FaseCronograma exige migration que troque este CHECK.
 */
return new class extends Migration
{
    private const CHECK = 'tdap_cronogramas_fase_tramite_check';

    private const FASES = ['elaboracao', 'em_analise', 'aprovado', 'em_execucao', 'entregue'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $lista = "'".implode("','", self::FASES)."'";

        $existe = DB::table('pg_constraint')
            ->where('conname', self::CHECK)
            ->where('contype', 'c')
            ->exists();

        if (! $existe) {
            DB::statement(
                'ALTER TABLE tdap_cronogramas ADD CONSTRAINT '.self::CHECK.
                " CHECK (fase_tramite IN ({$lista})) NOT VALID"
            );
        }

        // Normaliza pelo trigger o que estiver fora do dominio (esperado: 0 linhas).
        DB::statement(
            "UPDATE tdap_cronogramas SET fase_tramite = fase_tramite WHERE fase_tramite NOT IN ({$lista})"
        );

        DB::statement('ALTER TABLE tdap_cronogramas VALIDATE CONSTRAINT '.self::CHECK);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE tdap_cronogramas DROP CONSTRAINT IF EXISTS '.self::CHECK);
    }
};
