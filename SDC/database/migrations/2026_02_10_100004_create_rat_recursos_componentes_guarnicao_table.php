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
        if (Schema::hasTable('rat_recursos_componentes_guarnicao')) return;
        Schema::create('rat_recursos_componentes_guarnicao', function (Blueprint $table) {
            // Comentário da tabela
            $table->comment('Tabela de Componentes da Guarnição - Armazena informações dos agentes/servidores que participaram do atendimento');

            $table->id();
            
            // IDs de Relacionamento
            $table->unsignedBigInteger('recurso_empregado_id')->nullable()
                  ->comment('ID do Recurso Empregado');
            $table->unsignedBigInteger('relato_recurso_id')->nullable()
                  ->comment('ID do Relato Recurso');

            // Informações Pessoais e Funcionais
            $table->string('corporacao', 100)->nullable()->comment('Corporação');
            $table->string('matricula', 50)->nullable()->comment('Matrícula/NR/CPF');
            $table->string('masp', 20)->nullable()->comment('MASP - Matrícula de Serviço Público');
            $table->string('nome_completo', 255)->nullable()->comment('Nome Completo');
            $table->string('pg_cargo', 100)->nullable()->comment('PG/Cargo');
            $table->string('orgao', 255)->nullable()->comment('Órgão');
            $table->string('unidade', 255)->nullable()->comment('Unidade');
            $table->string('funcao', 255)->nullable()->comment('Função no Atendimento');

            // Flag de Condutor (Booleano)
            $table->boolean('is_condutor')->default(false)
                  ->comment('É o Condutor do veículo');

            // Auditoria
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Definição dos Índices conforme o SQL original
            $table->index('recurso_empregado_id', 'idx_rat_recursos_componentes_guarnicao_recurso_empregado_id');
            $table->index('relato_recurso_id', 'idx_rat_recursos_componentes_guarnicao_relato_recurso_id');
            $table->index('masp', 'idx_rat_recursos_componentes_guarnicao_masp');
            $table->index('matricula', 'idx_rat_recursos_componentes_guarnicao_matricula');
            $table->index('created_by', 'idx_rat_recursos_componentes_guarnicao_created_by');
            $table->index('created_at', 'idx_rat_recursos_componentes_guarnicao_created_at');

            // Configuracoes especificas do MySQL (Postgres ignora)
            if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_recursos_componentes_guarnicao');
    }
};
