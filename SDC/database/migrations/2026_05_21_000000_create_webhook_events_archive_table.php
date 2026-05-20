<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de arquivamento de webhook_events com status='completed'
     * e idade > 90 dias. Mantida pelo comando webhooks:archive (semanal).
     * Espelha o schema de webhook_events + coluna archived_at.
     */
    public function up(): void
    {
        Schema::create('webhook_events_archive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_event_id', 255)->index();
            $table->string('provider', 50);
            $table->string('event_type', 100)->nullable();
            $table->jsonb('payload');
            $table->string('signature', 512)->nullable();
            $table->string('status', 30);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->timestamp('archived_at')->useCurrent();

            $table->index(['provider', 'archived_at'], 'webhook_archive_provider_archived');
            $table->index('archived_at', 'webhook_archive_archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events_archive');
    }
};
