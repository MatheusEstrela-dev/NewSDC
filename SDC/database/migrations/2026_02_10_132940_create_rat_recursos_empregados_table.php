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
        Schema::create('rat_recursos_empregados_copia', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com Relato (Chave Estrangeira)
            $table->unsignedBigInteger('relato_recurso_id')->nullable();

            // Enums conforme o SQL
            $table->enum('recurso_tipo', ['viatura', 'pe'])
                  ->nullable()
                  ->comment('Tipo de Recurso');
            
            $table->string('recurso_problemas', 191)
                  ->nullable()
                  ->comment('Problemas durante o atendimento');
            
            $table->text('recurso_descricao')
                  ->nullable()
                  ->comment('Descrição do problema');

            $table->enum('viatura_tipo', ['Viatura Principal', 'Viatura de Apoio', 'Órgãos externos ao SISP'])
                  ->nullable()
                  ->comment('Tipo da Viatura');

            // Campos da Viatura
            $table->string('viatura_placa', 20)->nullable()->comment('Placa');
            $table->string('viatura_prefixo', 50)->nullable()->comment('Prefixo Numérico');
            $table->string('viatura_padrao', 50)->nullable()->comment('Prefixo Padrão');
            $table->string('viatura_orgao', 100)->nullable()->comment('Órgão');
            $table->text('viatura_descricao')->nullable()->comment('Descrição/Observação');

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('relato_recurso_id', 'rat_recursos_empregados_relato_recurso_id_index');
            $table->index('recurso_tipo', 'rat_recursos_empregados_recurso_tipo_index');
            $table->index('viatura_placa', 'rat_recursos_empregados_viatura_placa_index');
            $table->index('created_by', 'rat_recursos_empregados_created_by_index');
            $table->index('created_at', 'rat_recursos_empregados_created_at_index');

            // Definição da Foreign Key com Cascade
            $table->foreign('relato_recurso_id', 'rat_recursos_empregados_relato_recurso_id_foreign')
                  ->references('id')
                  ->on('rat_relato_recursos_copia')
                  ->onDelete('cascade');

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_recursos_empregados_copia');
    }
};