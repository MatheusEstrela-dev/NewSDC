<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_recebimentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recebimento', 50)->unique();
            $table->unsignedBigInteger('ordem_compra_id')->nullable(); // FK para sistema de compras
            $table->string('nota_fiscal', 100);
            $table->string('placa_veiculo', 20);
            $table->string('transportadora')->nullable();
            $table->string('motorista_nome');
            $table->string('motorista_documento', 20)->nullable();
            $table->string('doca_descarga', 20)->nullable();
            $table->datetime('data_chegada');
            $table->datetime('data_inicio_conferencia')->nullable();
            $table->datetime('data_fim_conferencia')->nullable();
            $table->foreignId('conferido_por')->nullable()->constrained('users');
            $table->foreignId('aprovado_por')->nullable()->constrained('users');
            $table->string('status', 50); // Enum: RecebimentoStatus
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_recebimento');
            $table->index('nota_fiscal');
            $table->index('status');
            $table->index('data_chegada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_recebimentos');
    }
};
