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
        Schema::create('orgao_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orgao_id')
                ->constrained('orgaos')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('funcao', ['coordenador', 'agente', 'tecnico', 'apoio'])
                ->default('agente')
                ->comment('Função do usuário no órgão');

            $table->boolean('is_principal')->default(false)
                ->comment('Se é o órgão principal do usuário');

            $table->timestamps();

            // Único: usuário não pode ter mesma função 2x no mesmo órgão
            $table->unique(['orgao_id', 'user_id', 'funcao'], 'unique_orgao_user_funcao');

            // Índices
            $table->index(['user_id', 'is_principal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orgao_user');
    }
};
