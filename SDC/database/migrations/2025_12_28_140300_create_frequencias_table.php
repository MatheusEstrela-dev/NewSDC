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
        Schema::create('frequencias', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('modulo_id')
                ->constrained('modulos')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Status da Frequência
            $table->enum('status', ['PRESENTE', 'AUSENTE', 'JUSTIFICADA'])
                ->default('PRESENTE')
                ->index();

            // Data da Aula
            $table->date('data_aula');

            // Observações
            $table->text('observacoes')->nullable();

            // Quem registrou
            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Índices
            $table->unique(['modulo_id', 'user_id', 'data_aula'], 'idx_frequencia_unique');
            $table->index(['modulo_id', 'data_aula']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frequencias');
    }
};
