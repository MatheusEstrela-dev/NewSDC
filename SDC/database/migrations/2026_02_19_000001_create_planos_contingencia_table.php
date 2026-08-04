<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_contingencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('municipio_id');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->enum('situacao', ['regular', 'irregular'])->default('regular');
            $table->date('data_aprovacao')->nullable();
            $table->date('data_validade')->nullable();
            $table->string('arquivo_url')->nullable();
            $table->text('observacoes')->nullable();

            // Dono do registro, para a trilha de acoes do sino (Rastreavel). A tabela nao
            // tinha nenhuma coluna que identificasse usuario: so municipio_id, que e
            // escopo territorial e nao responsabilidade sobre o plano.
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que cadastrou; recebe a trilha de acoes do registro');

            $table->timestamps();

            $table->index('municipio_id');
            $table->index('situacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos_contingencia');
    }
};
