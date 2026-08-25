<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela da Classificacao e Codificacao Brasileira de Desastres (COBRADE).
 *
 * Dimensoes: `codigo` tem formato fixo de 9 caracteres (1.1.3.1.1); `nome`
 * guarda a denominacao oficial (maior tem 86 caracteres); `descricao` guarda a
 * definicao oficial completa, que chega a 497 caracteres — por isso e `text` e
 * nao varchar. `grupo` e o primeiro nivel da hierarquia oficial (10 valores) e
 * existe para a tela filtrar os eventos em cascata em vez de listar os 65 de
 * uma vez. O conteudo e carregado por CobradeSeeder.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dec_cobrade', function (Blueprint $table) {
            $table->increments('id')->comment('Identificador do Cobrade');
            $table->string('codigo', 45)->nullable()->comment('codigo do cobrade');
            $table->text('descricao')->nullable()->comment('Definicao oficial do cobrade');
            $table->string('nome', 255)->nullable()->comment('Denominacao oficial do cobrade');
            $table->string('grupo', 255)->nullable()->comment('Grupo do desastre (Geologico, Hidrologico, ...)');

            $table->unique('codigo', 'dec_cobrade_codigo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dec_cobrade');
    }
};
