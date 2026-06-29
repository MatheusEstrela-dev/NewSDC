<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_planos', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 30)->nullable()->unique();
            $table->unsignedBigInteger('municipio_id');
            $table->string('status', 20)->default('RASCUNHO')->index();
            $table->timestamp('data')->nullable();           // data de criacao do plano
            $table->text('acoes')->nullable();
            $table->text('motivo')->nullable();               // Etapa 1: motivo/descricao do pedido
            // Etapa 6: acoes de resposta ja executadas pelo municipio
            $table->boolean('acao_decreto_se')->default(false);
            $table->boolean('acao_caminhao_pipa')->default(false);
            $table->boolean('acao_cestas_basicas')->default(false);
            $table->text('justificativa_apoio')->nullable();  // Etapa 6: justificativa da necessidade de apoio estadual
            $table->unsignedInteger('qtd_caminhao')->nullable();
            $table->unsignedInteger('pop_at_municipio')->nullable();
            $table->boolean('pedido_altera')->default(false);
            $table->boolean('alterar_com')->default(false);
            $table->string('resp_homolog', 100)->nullable();
            $table->timestamp('dt_analise')->nullable();
            $table->timestamp('dt_ultima_alteracao')->nullable();
            $table->timestamp('data_aprov')->nullable();
            $table->string('resp_estado', 100)->nullable();
            $table->timestamp('dt_estado')->nullable();
            // Dados ISS (especificos do plano; nome/uf do municipio derivam de "municipios")
            $table->boolean('cobra_iss')->nullable();
            $table->string('num_lei_iss', 30)->nullable();
            $table->decimal('aliquota_iss', 5, 2)->nullable();
            $table->string('resp_cob_iss', 30)->nullable();
            // Dados do Municipio/Prefeitura (snapshot do plano)
            $table->string('nome_prefeito', 110)->nullable();
            $table->string('tel_prefeitura', 20)->nullable();
            $table->string('tel_prefeito', 20)->nullable();
            $table->string('cel_prefeito', 20)->nullable();
            $table->string('endereco', 150)->nullable();
            $table->string('bairro', 60)->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('email_prefeitura', 110)->nullable();
            $table->unsignedInteger('populacao')->nullable();
            $table->unsignedInteger('pop_rural')->nullable();
            $table->decimal('area', 12, 2)->nullable();
            // Dados da COMPDEC (Coordenadoria Municipal de Protecao e Defesa Civil)
            $table->string('compdec_coordenador', 110)->nullable();
            $table->string('compdec_decreto', 50)->nullable();
            $table->string('compdec_lei', 50)->nullable();
            $table->string('compdec_tel', 20)->nullable();
            $table->string('compdec_email', 110)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('municipio_id')->references('id')->on('municipios');
        });

        DB::statement(
            "ALTER TABLE pmda_planos ADD CONSTRAINT chk_pmda_status CHECK (status IN ".
            "('RASCUNHO','COMPLETO','EM_ANALISE','APROVADO','ATENDIDO','ARQUIVADO','ANULADO','CANCELADO','ENCERRADO'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_planos');
    }
};
