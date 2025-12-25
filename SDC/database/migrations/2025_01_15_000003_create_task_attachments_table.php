<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration: Anexos das Tasks
     */
    public function up(): void
    {
        if (Schema::hasTable('task_attachments')) {
            return;
        }

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('nome_original', 255);
            $table->string('nome_arquivo', 255)
                ->comment('Nome do arquivo no storage');
            $table->string('mime_type', 100);
            $table->integer('tamanho_bytes');
            $table->string('path', 500);

            $table->timestamps();

            // Índices
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};
