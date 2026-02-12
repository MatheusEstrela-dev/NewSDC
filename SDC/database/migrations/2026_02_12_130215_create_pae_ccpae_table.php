<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pae_ccpae', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

            // Chave Estrangeira para pae_protocolos
            $table->foreignId('protocolo_id')
                  ->constrained('pae_protocolos')
                  ->onDelete('cascade');

            $table->string('codigo', 100)->unique('idx_ccpae_codigo_unique');
            
            // Data de emissão com valor default do banco (CURDATE)
            $table->date('dt_emissao')->default(DB::raw('(CURRENT_DATE)'));
            
            $table->date('dt_vencimento')->nullable();
            $table->string('status', 50)->default('ATIVO');

            // timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Índice manual adicional conforme seu SQL
            $table->index('protocolo_id', 'idx_ccpae_protocolo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_ccpae');
    }
};