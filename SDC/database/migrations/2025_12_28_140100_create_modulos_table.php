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
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();

            // Relacionamento
            $table->foreignId('treinamento_id')
                ->constrained('treinamentos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Informações
            $table->string('titulo', 255);
            $table->text('descricao')->nullable();

            // Ordem e Carga Horária
            $table->integer('ordem')->default(0)->comment('Ordem de exibição');
            $table->integer('carga_horaria')->comment('Carga horária do módulo em horas');

            // Data prevista para o módulo
            $table->date('data_prevista')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['treinamento_id', 'ordem']);
            $table->index('data_prevista');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
