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
        if (Schema::hasTable('rat_bem_afetado')) return;
        Schema::create('rat_bem_afetado', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT
            
            // Descrição com comentário e índice
            $table->string('descricao', 255)
                  ->comment('Descrição do bem afetado');
            
            // Campos de auditoria (created_by, updated_by)
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();
            
            // Timestamps (created_at, updated_at) e SoftDeletes (deleted_at)
            $table->timestamps();
            $table->softDeletes();

            // Adicionando o índice manualmente para a descrição
            $table->index('descricao', 'idx_rat_bem_afetado_descricao');
            
            // Configurações do Engine (opcional, mas fiel ao seu SQL)
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
        Schema::dropIfExists('rat_bem_afetado');
    }
};
