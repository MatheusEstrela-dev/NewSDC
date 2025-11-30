<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->json('payload');
            $table->integer('status_code')->default(0);
            $table->text('response')->nullable();
            $table->decimal('duration_ms', 10, 2)->default(0);
            $table->boolean('success')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('attempt')->default(1);
            $table->timestamp('created_at');

            // Índices para performance em 100k+ registros
            $table->index(['success', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('status_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
