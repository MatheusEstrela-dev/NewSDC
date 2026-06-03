<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_entrada_processos', function (Blueprint $table) {
            $table->id();

            $table->date('data_ocorrencia_desastre')->nullable();
            $table->date('data_entrada')->nullable();

            $table->string('processo')->nullable();
            $table->string('analista')->nullable();
            $table->string('n_protocolo_fide')->nullable();
            $table->string('decreto_municipal')->nullable();
            $table->string('status')->nullable();

            $table->unsignedBigInteger('tipo_desastre_id')->nullable();
            $table->string('tipo_desastre')->nullable();
            $table->unsignedBigInteger('redec_id')->nullable();
            $table->unsignedBigInteger('orgao_responsavel_id')->nullable();

            // Decreto Municipal
            $table->date('data_decreto_municipal')->nullable();
            $table->date('data_publicacao_mg')->nullable();
            $table->integer('prazo_vigencia')->nullable();

            // Reconhecimento Estadual
            $table->string('n_decreto_estadual')->nullable();
            $table->date('data_decreto_estadual')->nullable();
            $table->string('n_edicao_domg')->nullable();
            $table->date('data_publicacao_domg')->nullable();

            // Reconhecimento Federal
            $table->string('reconhecimento')->nullable();
            $table->string('reconhecimento_decreto_n_data')->nullable();
            $table->string('data_publicacao_diario')->nullable();
            $table->string('portaria_reconhecimento_fed')->nullable();
            $table->string('portaria_diario_oficial')->nullable();
            $table->string('reconhecimento_federal')->nullable();
            $table->date('data_portaria_federal')->nullable();
            $table->string('n_edicao_dou')->nullable();
            $table->date('data_publicacao_dou')->nullable();

            $table->text('observacoes')->nullable();
            $table->string('processo_inserido_sei')->nullable();
            $table->string('situacao_processo')->nullable();
            $table->text('area_afetada_geom')->nullable();

            $table->string('created_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_entrada_processos');
    }
};