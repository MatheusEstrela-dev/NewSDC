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
        if (Schema::hasTable('rat_relato_recursos')) return;
        Schema::create('rat_relato_recursos', function (Blueprint $table) {
            $table->comment('Tabela de Recursos do Relato - Armazena informações principais dos recursos utilizados no atendimento');
            
            $table->id();
            
            // Sequencial e Tipo (Enum)
            $table->integer('seq')->comment('Sequencial do Recurso');
            $table->enum('recurso_tipo', ['viatura', 'pe', 'aereo', 'aquatico', 'outro'])
                  ->nullable()
                  ->comment('Tipo de Recurso');

            // Problemas e Descrições
            $table->boolean('recurso_problemas')->default(0)->comment('Problemas durante o atendimento');
            $table->text('recurso_descricao')->nullable()->comment('Descrição do problema');

            // Detalhes da Viatura
            $table->string('viatura_tipo', 100)->nullable()->comment('Tipo da Viatura');
            $table->string('viatura_placa', 20)->nullable()->comment('Placa da viatura');
            $table->string('viatura_prefixo', 50)->nullable()->comment('Prefixo Numérico');
            $table->string('viatura_padrao', 50)->nullable()->comment('Prefixo Padrão');
            $table->string('viatura_orgao', 255)->nullable()->comment('Órgão responsável');
            $table->text('viatura_descricao')->nullable()->comment('Descrição/Observação');

            // Logística (Datas e KM)
            $table->dateTime('viatura_saida')->nullable()->comment('Data/Hora de Saída');
            $table->dateTime('viatura_chegada')->nullable()->comment('Data/Hora de Chegada');
            $table->decimal('viatura_km', 10, 2)->nullable()->comment('KM Percorrido');
            
            $table->string('viatura_local_origem', 255)->nullable()->comment('Local de Origem');
            $table->string('viatura_local_destino', 255)->nullable()->comment('Local de Destino');
            $table->integer('viatura_quantidade')->default(1)->comment('Quantidade de Recursos');
            $table->string('viatura_capacidade', 100)->nullable()->comment('Capacidade/Potência');

            // Condição e Operador
            $table->enum('viatura_condicao', ['otima', 'boa', 'regular', 'ruim', 'inoperante'])
                  ->nullable()
                  ->comment('Condição do Recurso');
            
            $table->string('viatura_operador', 255)->nullable()->comment('Operador/Responsável');
            $table->string('operador_masp', 20)->nullable()->comment('MASP do Operador');
            $table->boolean('operador_is_condutor')->default(0)->comment('Indica se é o condutor');
            $table->string('viatura_contato', 50)->nullable()->comment('Contato de Emergência');

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('seq', 'idx_rat_relato_recursos_seq');
            $table->index('recurso_tipo', 'idx_rat_relato_recursos_tipo');
            $table->index('viatura_placa', 'idx_rat_relato_recursos_placa');
            $table->index('created_by', 'idx_rat_relato_recursos_created_by');
            $table->index('created_at', 'idx_rat_relato_recursos_created_at');

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
        Schema::dropIfExists('rat_relato_recursos');
    }
};
