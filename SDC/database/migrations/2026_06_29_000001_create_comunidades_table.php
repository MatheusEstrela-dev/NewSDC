<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro mestre de comunidades por municipio (reutilizavel em qualquer PMDA).
 * Espelha o legado pip_comunidade: comunidades aprovadas pela CEDEC ficam aqui
 * e passam a aparecer no seletor "Adicionar Comunidade" da aba de distribuicao.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('comunidades', function (Blueprint $table) {
            $table->id();

            // Chave do registro correspondente em pip_comunidade. E o que torna
            // o ETL (pmda:migrar-comunidades-legado) repetivel: sem ela, uma
            // segunda execucao nao reconhece o que ja trouxe. Null para
            // comunidade nascida no sistema novo.
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();

            $table->unsignedBigInteger('municipio_id')->index();
            $table->string('nome', 150);
            $table->string('latitude', 30)->nullable();
            $table->string('longitude', 30)->nullable();

            // Valores de referencia da comunidade, nao do plano.
            //
            // O legado guardava trecho_pav/trecho_n_pav/pop_atendida em
            // pip_comunidade E os copiava para cada pip_pmda_comun. O schema
            // novo separou: o numero que vale e sempre o de pmda_comunidades,
            // por plano. Estes aqui sao a ultima referencia conhecida, usada
            // para pre-preencher o formulario quando a comunidade e adicionada
            // a um novo PMDA (ver PmdaService::adicionar). Preservam o dado do
            // legado em vez de descarta-lo, sem virar fonte de verdade.
            $table->decimal('trecho_pav', 8, 2)->nullable();
            $table->decimal('trecho_n_pav', 8, 2)->nullable();
            $table->unsignedInteger('pop_atendida')->nullable();

            // pip_comunidade.id_ponto apontava para o ponto de captacao legado.
            // Os pontos ainda nao foram migrados (pip_pmda_ponto novo tem 14
            // linhas contra 216 no legado), entao nao ha id novo para traduzir.
            // Guardado cru para que o vinculo seja recuperavel por um UPDATE
            // quando o ETL de pontos existir - descartar seria irreversivel.
            $table->unsignedBigInteger('ponto_legacy_id')->nullable()->index();

            $table->boolean('ativo')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('municipio_id')->references('id')->on('municipios')->cascadeOnDelete();
            $table->unique(['municipio_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunidades');
    }
};
