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
        Schema::create('pae_dilacoes', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

            // Relacionamento com Protocolos
            $table->foreignId('protocolo_id')
                  ->constrained('pae_protocolos')
                  ->onDelete('cascade');

            $table->string('status', 50)->default('PENDENTE');
            $table->integer('dias_adicionais');
            $table->text('justificativa')->nullable();

            // Relacionamento com Usuário (Quem aprovou)
            $table->foreignId('aprovado_por')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // created_at e updated_at com comportamento de timestamp do MySQL
            $table->timestamps();

            // Índices manuais para garantir performance nas buscas
            $table->index('protocolo_id', 'idx_dilacoes_protocolo');
            $table->index('aprovado_por', 'idx_dilacoes_aprovado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_dilacoes');
    }
};