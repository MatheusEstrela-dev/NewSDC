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
        // ========================
        // DADOS GERAIS
        // ========================
        if (!Schema::hasTable('rat_relato_dados_gerais')) {
            Schema::create('rat_relato_dados_gerais', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ocorrencia_id')->nullable();
                $table->unsignedBigInteger('usuario_id');

                // Identificação do BO
                $table->string('numero_bos')->unique();
                $table->dateTime('data_ocorrencia');
                $table->time('hora_ocorrencia')->nullable();

                // Localização
                $table->string('local_origem');
                $table->string('local_destino')->nullable();
                $table->decimal('km_percorrido', 10, 2)->nullable();

                // Classificação
                $table->string('natureza'); // NAT_CODIGO
                $table->string('categoria');
                $table->string('orgao_responsavel');

                // Descrição
                $table->longText('descricao')->nullable();

                // Status
                $table->enum('status', ['rascunho', 'em_andamento', 'finalizado', 'cancelado'])->default('rascunho');

                // Auditoria
                $table->timestamp('data_criacao')->useCurrent();
                $table->timestamp('data_atualizacao')->useCurrent();
                $table->timestamps();

                // Índices
                $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            });
        } else {
            Schema::table('rat_relato_dados_gerais', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                    $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                }
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'descricao')) {
                    $table->longText('descricao')->nullable()->after('orgao_responsavel');
                }
                if (!Schema::hasColumn('rat_relato_dados_gerais', 'status')) {
                    $table->enum('status', ['rascunho', 'em_andamento', 'finalizado', 'cancelado'])->default('rascunho')->after('descricao');
                }
            });
        }

        // ========================
        // ENVOLVIDOS
        // ========================
        if (! Schema::hasTable('rat_relato_envolvidos')) {
            Schema::create('rat_relato_envolvidos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ocorrencia_id');
                $table->unsignedBigInteger('usuario_id');

                // Dados Pessoais
                $table->string('nome_completo');
                $table->string('cpf')->nullable()->unique();
                $table->date('data_nascimento')->nullable();
                $table->string('escolaridade')->nullable();
                $table->string('profissao')->nullable();
                $table->enum('sexo', ['M', 'F'])->nullable();
                $table->string('estado_civil')->nullable();
                $table->string('rg')->nullable();
                $table->string('fone')->nullable();
                $table->string('email')->nullable();

                // Comercial
                $table->string('cnaes')->nullable();

                // Endereço
                $table->string('endereco');
                $table->string('numero')->nullable();
                $table->string('complemento')->nullable();
                $table->string('bairro');
                $table->string('cidade');
                $table->string('uf', 2);
                $table->string('cep')->nullable();

                // Contato
                $table->string('telefone_comercial')->nullable();
                $table->string('telefone_referencia')->nullable();

                // Tipo de Envolvimento
                $table->string('tipo_envolvimento'); // vítima, agressor, testemunha, etc.

                // Auditoria
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                // Índices
                $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
                $table->index(['ocorrencia_id', 'tipo_envolvimento']);
            });
        } else {
            Schema::table('rat_relato_envolvidos', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_envolvidos', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                    $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                }
            });
        }

        // ========================
        // RECURSOS
        // ========================
        if (! Schema::hasTable('rat_relato_recursos')) {
            Schema::create('rat_relato_recursos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ocorrencia_id');
                $table->unsignedBigInteger('usuario_id');

                // Classificação
                $table->string('tipo_recurso'); // veículo, pessoal, material
                $table->string('categoria');
                $table->string('orgao_responsavel');
                $table->string('identificacao');
                $table->text('descricao')->nullable();

                // Localização e Horários
                $table->string('local_origem');
                $table->string('local_destino')->nullable();
                $table->dateTime('data_hora_saida')->nullable();
                $table->dateTime('data_hora_chegada')->nullable();
                $table->decimal('km_percorrido', 10, 2)->nullable();

                // Observações
                $table->text('observacoes')->nullable();

                // Auditoria
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                // Índices
                $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
                $table->index(['ocorrencia_id', 'tipo_recurso']);
            });
        } else {
            Schema::table('rat_relato_recursos', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_recursos', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                    $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                }
            });
        }

        // ========================
        // VISTORIA
        // ========================
        if (! Schema::hasTable('rat_relato_vistorias')) {
            Schema::create('rat_relato_vistorias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ocorrencia_id');
                $table->unsignedBigInteger('usuario_id');

                // Dados da Vistoria
                $table->date('data_vistoria');
                $table->string('local_vistoria');
                $table->text('descricao_danos')->nullable();
                $table->text('parecer');
                $table->text('recomendacoes')->nullable();

                // Profissional
                $table->string('profissional');
                $table->string('cargo');
                $table->string('orgao');
                $table->string('numero_register');

                // Auditoria
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                // Índices
                $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
            });
        } else {
            Schema::table('rat_relato_vistorias', function (Blueprint $table) {
                if (!Schema::hasColumn('rat_relato_vistorias', 'ocorrencia_id')) {
                    $table->unsignedBigInteger('ocorrencia_id')->nullable()->after('id');
                    $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                }
            });
        }

        // ========================
        // HISTÓRICO
        // ========================
        if (!Schema::hasTable('rat_ocorrencia_historicos')) {
            Schema::create('rat_ocorrencia_historicos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ocorrencia_id');
                $table->unsignedBigInteger('usuario_id');

                $table->string('acao'); // criado, atualizado, finalizado, etc
                $table->text('detalhes')->nullable();
                $table->string('entidade'); // qual relato foi afetado
                $table->unsignedBigInteger('entidade_id');

                // Auditoria
                $table->timestamp('created_at')->useCurrent();

                // Índices
                $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->onDelete('cascade');
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('restrict');
                $table->index(['ocorrencia_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_ocorrencia_historicos');
        Schema::dropIfExists('rat_relato_vistorias');
        Schema::dropIfExists('rat_relato_recursos');
        Schema::dropIfExists('rat_relato_envolvidos');
        Schema::dropIfExists('rat_relato_dados_gerais');
    }
};
