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
        Schema::create('pae_form_templates', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT

            $table->string('categoria', 50);
            $table->string('titulo', 255);
            $table->text('conteudo');
            $table->integer('ordem')->default(0);
            
            // Campo JSON para sub-itens estruturados
            $table->json('sub_itens_json')->nullable();
            
            $table->boolean('ativo')->default(true);
            $table->integer('versao')->default(1);
            $table->boolean('vigente')->default(true);
            
            $table->date('data_vigencia_inicio')->nullable();
            $table->date('data_vigencia_fim')->nullable();
            $table->text('motivo_alteracao')->nullable();

            // Auto-referência para versionamento
            $table->foreignId('versao_anterior_id')
                  ->nullable()
                  ->constrained('pae_form_templates')
                  ->onDelete('set null');

            // created_at e updated_at
            $table->timestamps();

            // Índices Manuais
            $table->index('categoria', 'idx_templates_categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_form_templates');
    }
};