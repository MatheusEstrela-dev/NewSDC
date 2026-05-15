<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Read Model / Projecao do ProcessoTdap.
 *
 * Desnormalizada para alimentar dashboards (SwimlaneBoard) sem joins pesados.
 * Atualizada APENAS por listeners de projecao via Outbox; nunca escrita por
 * Service direto. Pode ser reconstruida do zero com `php artisan event:replay`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_processo_projecoes', function (Blueprint $table) {
            $table->uuid('processo_id')->primary();
            $table->string('numero', 20)->unique();
            $table->string('estado', 30)->index();
            $table->string('swimlane_atual', 30)->index();

            $table->unsignedBigInteger('municipio_id');
            $table->string('municipio_nome', 150)->nullable();
            $table->string('municipio_uf', 2)->nullable();

            $table->unsignedBigInteger('prestador_id')->nullable();
            $table->string('prestador_nome', 150)->nullable();

            $table->unsignedInteger('total_cronogramas')->default(0);
            $table->decimal('total_agua_prevista_m3', 14, 2)->default(0);
            $table->decimal('total_agua_entregue_m3', 14, 2)->default(0);
            $table->unsignedInteger('total_viagens_validadas')->default(0);

            $table->unsignedInteger('dias_no_estado_atual')->default(0);
            $table->string('ultimo_evento', 60)->nullable();
            $table->timestampTz('atualizado_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_processo_projecoes');
    }
};
