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
        Schema::create('rat_ocorrencias_copia', function (Blueprint $table) {
            $table->id();
            
            // Número BOS com Unique e Comentário
            $table->string('numero_bos', 50)->nullable()
                  ->unique('rat_ocorrencias_numero_bos_unique')
                  ->comment('Número do BOS no formato YYYY-SEQUENCIAL-001');
            
            $table->unsignedBigInteger('sequencial_ano')->nullable()
                  ->comment('Sequencial do BOS no ano corrente');
            
            $table->string('created_by', 191)->nullable();
            
            // Status tinyint com default 0
            $table->tinyInteger('status')->default(0)
                  ->comment('0=Rascunho, 1=Finalizado');
            
            $table->timestamp('prazo_edicao')->nullable()
                  ->comment('Prazo limite para edição do rascunho');
            
            $table->string('updated_by', 191)->nullable();
            
            // Chave Estrangeira autorreferenciada (BOS pai)
            $table->unsignedBigInteger('ocorrencia_origem_id')->nullable()
                  ->comment('ID da ocorrência de origem (BOS pai)');
            
            // Histórico do tipo TEXT
            $table->text('historico')->nullable()
                  ->comment('Histórico da ocorrência');

            // Timestamps e SoftDeletes
            $table->timestamps();
            $table->softDeletes();

            // Definição da Foreign Key e Constraints
            $table->foreign('ocorrencia_origem_id', 'rat_ocorrencias_ocorrencia_origem_id_foreign')
                  ->references('id')
                  ->on('rat_ocorrencias_copia')
                  ->onDelete('set null');

            // Configurações do Banco
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
        Schema::dropIfExists('rat_ocorrencias_copia');
    }
};