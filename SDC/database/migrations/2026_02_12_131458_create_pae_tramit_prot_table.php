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
        if (!Schema::hasTable('pae_tramit_prot')) {
            Schema::create('pae_tramit_prot', function (Blueprint $table) {
                $table->id()->comment('Identificador da Tabela');

                // Usuário que realizou a tramitação
                $table->foreignId('user_id')
                      ->nullable()
                      ->comment('Identificador do Usuário')
                      ->constrained('users')
                      ->onDelete('set null');

                $table->string('status', 20)->nullable()->comment('Status');

                // Timestamp que atualiza automaticamente na alteração (comportamento do SQL original)
                $table->timestamp('dt_status')
                      ->useCurrent()
                      
                      ->comment('Data de Movimentação');

                $table->string('obs', 100)->nullable()->comment('Observação');

                // Vínculo com o Protocolo
                $table->foreignId('protocolo_id')
                      ->comment('Identificador do Protocolo')
                      ->constrained('pae_protocolos')
                      ->onDelete('cascade');

                // Índices manuais conforme definido no seu SQL
                $table->index('protocolo_id', 'idx_tramit_protocolo');
                $table->index('user_id', 'idx_tramit_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_tramit_prot');
    }
};