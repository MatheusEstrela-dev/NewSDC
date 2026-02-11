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
        if (Schema::hasTable('rat_encaminhamento')) return;
        Schema::create('rat_encaminhamento', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT
            
            // Descrição com comentário e índice específico
            $table->string('descricao', 255)
                  ->comment('Descrição do encaminhamento');
            
            // Auditoria
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            
            // Timestamps padrão (created_at e updated_at)
            $table->timestamps();
            
            // Soft Deletes (deleted_at)
            $table->softDeletes();

            // Índice para a coluna descrição
            $table->index('descricao', 'idx_rat_encaminhamento_descricao');
            
            // Definições de Engine e Charset
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_encaminhamento');
    }
};
