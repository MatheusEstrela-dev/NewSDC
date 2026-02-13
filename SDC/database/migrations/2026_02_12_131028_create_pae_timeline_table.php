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
        if (!Schema::hasTable('pae_timeline')) {
            Schema::create('pae_timeline', function (Blueprint $table) {
                $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

                // Relacionamento com o Protocolo
                $table->foreignId('protocolo_id')
                      ->constrained('pae_protocolos')
                      ->onDelete('cascade');

                $table->string('evento', 100);
                $table->text('descricao')->nullable();

                // Usuário que gerou o evento (opcional)
                $table->foreignId('user_id')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                // Criado em (timestamp NULL DEFAULT CURRENT_TIMESTAMP)
                $table->timestamp('created_at')->useCurrent();
                
                // Índices manuais para garantir a velocidade da timeline
                $table->index('protocolo_id', 'idx_timeline_protocolo');
                $table->index('user_id', 'idx_timeline_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_timeline');
    }
};