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
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('treinamento_id')
                ->constrained('treinamentos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Status da Inscrição
            $table->enum('status', ['PENDENTE', 'APROVADA', 'REPROVADA', 'CANCELADA'])
                ->default('PENDENTE')
                ->index();

            // Datas
            $table->timestamp('data_inscricao')->useCurrent();
            $table->timestamp('data_aprovacao')->nullable();

            // Aprovação
            $table->foreignId('aprovado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacoes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices compostos
            $table->unique(['treinamento_id', 'user_id'], 'idx_treinamento_user_unique');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscricoes');
    }
};
