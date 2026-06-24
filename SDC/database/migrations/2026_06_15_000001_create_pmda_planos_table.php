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
            // Dados ISS (especificos do plano; demais dados do municipio derivam de "municipios")
            $table->boolean('cobra_iss')->nullable();
            $table->string('num_lei_iss', 30)->nullable();
            $table->decimal('aliquota_iss', 5, 2)->nullable();
            $table->string('resp_cob_iss', 30)->nullable();
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
