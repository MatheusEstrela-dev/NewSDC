<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Transactional Outbox.
 *
 * Eventos de dominio sao persistidos aqui DENTRO da mesma transacao que altera
 * o agregado, garantindo "exactly-once delivery" (sem perda nem duplicacao).
 *
 * Worker (php artisan outbox:dispatch) consome eventos com dispatched_at NULL,
 * publica via Laravel events e marca como dispatched. Postgres SKIP LOCKED
 * permite multiplos workers em paralelo sem contencao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_name', 150)->index();
            $table->unsignedTinyInteger('event_version')->default(1);
            $table->string('aggregate_type', 100)->index();
            // varchar, nao uuid: o outbox e generico e recebe agregados de
            // chave inteira (Cronograma, CronoViagem) alem dos de UUID
            // (ProcessoTdap). Ver 2026_08_21_110000_widen_outbox_events_aggregate_id.
            $table->string('aggregate_id', 64)->index();
            $table->jsonb('payload');
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('dispatched_at')->nullable()->index();
            $table->unsignedSmallInteger('dispatch_attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['dispatched_at', 'occurred_at'], 'outbox_pending_idx');
            $table->index(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
