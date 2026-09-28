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

            // Anexo importado do legado sem o arquivo fisico no disco de origem:
            // o registro (vinculo com a demanda) e mantido, so o conteudo binario
            // que falta. checksum_sha256 confirma integridade do que foi copiado.
            $table->boolean('arquivo_disponivel')->default(true);
            $table->string('checksum_sha256', 64)->nullable();

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
