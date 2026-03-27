<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de Ocorrências RAT
        Schema::create('rat_ocorrencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_protocolo')->unique()->nullable();
            $table->enum('status', ['rascunho', 'em_andamento', 'concluido', 'cancelado'])->default('rascunho');
            $table->string('tipo_rat')->nullable();
            $table->dateTime('data_inicio')->nullable();
            $table->dateTime('data_fim')->nullable();
            $table->uuid('orgao_emissor_id')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->unsignedInteger('pais_id')->default(1);
            $table->text('descricao_sumaria')->nullable();
            $table->text('observacoes_gerais')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_protocolo');
            $table->index('status');
            $table->index('created_by');
        });

        // Tabela de Dados Gerais
        Schema::create('rat_relato_dados_gerais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ocorrencia_id')->nullable();
            $table->string('tipo_recurso')->nullable();
            $table->string('categoria')->nullable();
            $table->text('descricao')->nullable();
            $table->date('data_deslocamento')->nullable();
            $table->time('hora_deslocamento')->nullable();
            $table->string('local_origem')->nullable();
            $table->string('local_destino')->nullable();
            $table->decimal('quilometragem', 10, 2)->nullable();
            $table->date('data_chegada')->nullable();
            $table->time('hora_chegada')->nullable();
            $table->string('local_fato')->nullable();
            $table->string('endereco_fato')->nullable();
            $table->string('lat_long_fato')->nullable();
            $table->decimal('km_percorrido', 10, 2)->nullable();
            $table->decimal('valor_km', 10, 2)->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias');
            $table->index('ocorrencia_id');
        });

        // Tabela de Envolvidos
        Schema::create('rat_relato_envolvidos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ocorrencia_id')->nullable();
            $table->string('tipo_pessoa')->nullable();
            $table->string('nome_completo')->nullable();
            $table->string('matricula_masf')->nullable();
            $table->string('tipo_identificacao')->nullable();
            $table->string('numero_identificacao')->nullable();
            $table->string('orgao_expedidor')->nullable();
            $table->string('cargo')->nullable();
            $table->string('orgao')->nullable();
            $table->string('unidade')->nullable();
            $table->string('funcao_atendimento')->nullable();
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();
            $table->string('cpf')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('sexo')->nullable();
            $table->string('nacionalidade')->nullable();
            $table->string('estado_civil')->nullable();
            $table->string('profissao')->nullable();
            $table->string('contato_emergencia')->nullable();
            $table->string('telefone_emergencia')->nullable();
            $table->text('endereco_completo')->nullable();
            $table->string('cep')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->string('pais')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias');
            $table->index('ocorrencia_id');
            $table->index('cpf');
        });

        // Tabela de Recursos
        Schema::create('rat_relato_recursos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ocorrencia_id')->nullable();
            $table->string('nome_recurso')->nullable();
            $table->string('tipo_recurso')->nullable();
            $table->string('placa_identificacao')->nullable();
            $table->integer('capacidade')->nullable();
            $table->string('compatibilidade')->nullable();
            $table->decimal('valor_diaria', 10, 2)->nullable();
            $table->integer('quantidade')->nullable();
            $table->dateTime('periodo_utilizacao_inicio')->nullable();
            $table->dateTime('periodo_utilizacao_fim')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('proprietario')->nullable();
            $table->string('contato_proprietario')->nullable();
            $table->string('telefone_proprietario')->nullable();
            $table->string('situacao_recurso')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias');
            $table->index('ocorrencia_id');
        });

        // Tabela de Vistorias
        Schema::create('rat_relato_vistorias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ocorrencia_id')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('verso')->nullable();
            $table->string('pagina')->nullable();
            $table->integer('numero_pagina')->nullable();
            $table->decimal('patrimonio_bruto', 15, 2)->nullable();
            $table->decimal('patrimonio_liquido', 15, 2)->nullable();
            $table->string('documentacao_identificacao')->nullable();
            $table->string('compatibilidade')->nullable();
            $table->string('distribuicao')->nullable();
            $table->decimal('valor_avaliado', 15, 2)->nullable();
            $table->date('data_vistoria')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();
            $table->string('responsavel_vistoria')->nullable();
            $table->string('matricula_responsavel')->nullable();
            $table->text('assinatura')->nullable();
            $table->text('observacoes_tecnicas')->nullable();
            $table->text('conclusoes')->nullable();
            $table->text('recomendacoes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias');
            $table->index('ocorrencia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_relato_vistorias');
        Schema::dropIfExists('rat_relato_recursos');
        Schema::dropIfExists('rat_relato_envolvidos');
        Schema::dropIfExists('rat_relato_dados_gerais');
        Schema::dropIfExists('rat_ocorrencias');
    }
};
