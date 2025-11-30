<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('integration_id', 50)->unique();
            $table->string('type', 50); // rest_api, soap, graphql, webhook, etc
            $table->string('action', 100);
            $table->string('endpoint', 500)->nullable();
            $table->json('payload');
            $table->json('response')->nullable();
            $table->decimal('duration_ms', 10, 2)->default(0);
            $table->boolean('success')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');

            // Índices para consultas rápidas em alta escala
            $table->index(['type', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('integration_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
