<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotencia de Listeners.
 *
 * Cada par (event_id, listener_class) registrado garante que um listener
 * nao processa o mesmo evento duas vezes mesmo em retries de fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_events', function (Blueprint $table) {
            $table->uuid('event_id');
            $table->string('listener_class', 200);
            $table->timestampTz('processed_at')->useCurrent();
            $table->primary(['event_id', 'listener_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_events');
    }
};
