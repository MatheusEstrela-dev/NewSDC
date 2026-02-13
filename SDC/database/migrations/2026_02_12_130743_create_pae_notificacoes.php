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
        if (!Schema::hasTable('pae_notificacoes')) {
            Schema::create('pae_notificacoes', function (Blueprint $table) {
                $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

                $table->string('num_sei', 110);
                
                // Usuário que enviou a notificação
                $table->foreignId('user_id')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                // Vínculo com a análise que originou a notificação
                $table->foreignId('pae_analise_id')
                      ->constrained('pae_analises')
                      ->onDelete('cascade');

                $table->date('dt_notificacao');
                $table->boolean('prorrogacao')->default(false);
                $table->date('dt_devolutiva')->nullable();
                $table->text('obs')->nullable();

                // Timestamps e SoftDeletes
                $table->timestamps();
                $table->softDeletes();

                // Índices Manuais
                $table->index('pae_analise_id', 'idx_notificacoes_analise');
                $table->index('user_id', 'idx_notificacoes_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_notificacoes');
    }
};