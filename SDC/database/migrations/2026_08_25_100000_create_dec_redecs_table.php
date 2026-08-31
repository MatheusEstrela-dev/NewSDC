<?php

declare(strict_types=1);

use App\Modules\Decretacoes\Services\RedecService;
use Database\Seeders\RedecSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria `dec_redecs`: catalogo das Regioes de Defesa Civil de Minas Gerais.
 *
 * MOTIVO: a lista das REDECs vivia num enum PHP (App\Modules\Decretacoes\Enums\
 * Redec) com as sedes num `match`. Toda correcao de sede e toda regional nova
 * exigia deploy - e enquanto o enum conhecia apenas 14 regionais, as REDECs 15 a
 * 19 nao apareciam em lista suspensa nenhuma, nao podiam ser filtradas nem
 * exportadas. Agora o catalogo e dado: banco -> DTO -> front.
 *
 * A antiga `rat_redec` (modulo RAT) foi removida por
 * 2026_05_19_100000_drop_unused_rat_tables por nao ter model nem uso; esta e a
 * tabela do modulo de Decretacoes, com o prefixo `dec_` dos demais.
 *
 * CHAVE PRIMARIA SEM AUTO-INCREMENTO: o id E o numero da REDEC, o mesmo do
 * legado (`cedec_municipio.redec_id`), do qual sai a correspondencia municipio
 * -> REDEC e os valores ja gravados em `dec_entrada_processos.redec_id`. Deixar
 * a sequence decidir arriscaria renumerar as regionais.
 *
 * A carga inicial roda aqui, e nao so no seeder, para que um `migrate` sem
 * `db:seed` (o caso de producao) nao deixe os selects de REDEC vazios.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dec_redecs')) {
            Schema::create('dec_redecs', function (Blueprint $table) {
                // Sem ->id(): o numero da REDEC e a chave (ver cabecalho).
                $table->unsignedBigInteger('id')->primary();

                $table->string('sigla', 20)->comment('Ex: "3ª REDEC"');
                $table->string('sede', 120)->comment('Cidade sede da regional');
                $table->string('rpm', 20)->nullable()->comment('Regiao da PM correspondente, ex: "3ª RPM"');
                $table->string('nome', 150)->comment('Nome completo para relatorios');

                // Regional descontinuada some das listas suspensas sem apagar o
                // historico dos processos que ja apontam para ela.
                $table->boolean('ativo')->default(true)->index();

                $table->timestamps();
            });
        }

        // Idempotente: reexecucao apenas atualiza os rotulos.
        DB::table('dec_redecs')->upsert(
            RedecSeeder::linhas(),
            ['id'],
            ['sigla', 'sede', 'rpm', 'nome', 'updated_at']
        );

        // Best-effort: numa reexecucao que corrija rotulos, o cache do catalogo
        // ficaria ate 24h desatualizado. Cache fora do ar nao falha a migration.
        try {
            RedecService::clearCache();
        } catch (\Throwable) {
            // Segue o baile: TTL de 24h ou `php artisan cache:clear`.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_redecs');
    }
};
