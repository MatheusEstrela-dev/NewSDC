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
        Schema::create('rat_ocorrencia_relatos_copia', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT
            
            // Criado por (usando 191 conforme seu SQL)
            $table->string('created_by', 191)->nullable();
            
            // Relacionamento com a ocorrência
            $table->unsignedBigInteger('ocorrencia_id')->nullable();
            
            // Colunas para Relacionamento Polimórfico (conteudo_id e conteudo_type)
            $table->unsignedBigInteger('conteudo_id')->nullable();
            $table->string('conteudo_type', 191)->nullable();
            
            // Timestamps e SoftDeletes
            $table->timestamps();
            $table->softDeletes();
            
            // Configurações de Banco
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
        Schema::dropIfExists('rat_ocorrencia_relatos_copia');
    }
};