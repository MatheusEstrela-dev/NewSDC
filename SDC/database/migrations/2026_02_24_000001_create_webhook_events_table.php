<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de idempotencia para webhooks recebidos
     * Evita processamento duplicado de eventos
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_event_id', 255)->index();
            $table->string('provider', 50);
            $table->string('event_type', 100)->nullable();
            $table->jsonb('payload');
            $table->index('payload', 'idx_webhook_events_payload', 'gin');
            $table->string('signature', 512)->nullable();
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'dead_letter',
            ])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indices para idempotencia e consultas
            $table->unique(['external_event_id', 'provider'], 'webhook_events_idempotency');
            $table->index(['status', 'created_at'], 'webhook_events_status_date');
            $table->index(['provider', 'status'], 'webhook_events_provider_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
