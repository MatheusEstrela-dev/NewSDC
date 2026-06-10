<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ponto de captacao (PMDA) usado pelo cronograma TDAP.
 *
 * Equivalente novo da tabela legada pip_ponto_cap (gestaocedec). Mantem o nome
 * pip_pmda_ponto conforme o ERD do schema Laravel novo (PMDA_TDAP_DATABASE_ERD).
 * Deve rodar antes de tdap_cronogramas para viabilizar a FK ponto_captacao_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pip_pmda_ponto', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nome', 150);

            // tipo 1..6: COPASA, COPANOR, BARRAGEM, SAAE/DMAE, POCO PUBLICO, POCO PARTICULAR
            $table->unsignedSmallInteger('tipo')->default(1);

            $table->string('latitude', 30)->nullable();
            $table->string('longitude', 30)->nullable();
            $table->decimal('capacidade', 12, 2)->default(0)->comment('Capacidade em m3');

            $table->boolean('ativo')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index('municipio_id');
            $table->index(['municipio_id', 'ativo']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pip_pmda_ponto ADD CONSTRAINT pip_pmda_ponto_tipo_check CHECK (tipo BETWEEN 1 AND 6)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pip_pmda_ponto');
    }
};
