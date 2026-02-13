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
        if (!Schema::hasTable('pae_form_itens')) {
            Schema::create('pae_form_itens', function (Blueprint $table) {
                $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

                // Relacionamento com o Formulário Pai
                $table->foreignId('pae_form_id')
                      ->constrained('pae_forms')
                      ->onDelete('cascade');

                // Auto-relacionamento para estrutura hierárquica
                $table->foreignId('parent_id')
                      ->nullable()
                      ->constrained('pae_form_itens')
                      ->onDelete('cascade');

                $table->string('status', 50)->default('CONFORME');
                $table->string('categoria', 50)->nullable();
                $table->integer('ordem')->default(0);
                $table->text('conteudo')->nullable();

                // Usando timestamps padrão do Laravel (adiciona created_at e updated_at)
                // Ou se quiser exatamente como no SQL (apenas updated_at):
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                
                // Se o seu sistema Laravel for gerenciar o tempo de criação, 
                // recomendo usar $table->timestamps(); no lugar da linha acima.

                // Índices manuais para performance
                $table->index('pae_form_id', 'idx_form_itens_form');
                $table->index('parent_id', 'idx_form_itens_parent');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_form_itens');
    }
};