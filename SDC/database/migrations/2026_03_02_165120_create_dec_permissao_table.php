<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_permissao', function (Blueprint $table) {

            $table->increments('id_permissao'); // int auto increment

            $table->string('login', 9)->nullable();

            $table->integer('nivel')->nullable();

            $table->boolean('cad_decreto')->default(0);

            $table->boolean('relatorio')
                  ->default(0)
                  ->comment('Acesso aos Relatórios');

            $table->boolean('rel_resumo')
                  ->default(0)
                  ->comment('Acesso ao Resumo dos Processos');

            $table->unsignedInteger('id_usuario')
                  ->nullable()
                  ->comment('Identificador do Usuario');

            $table->boolean('edit_decreto')
                  ->default(0)
                  ->comment('Permissao Editar analisar Decreto');

            $table->foreign('id_usuario')
                  ->references('id')
                  ->on('cedec_usuario');

            $table->index('id_usuario', 'fk_id_usuario_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_permissao');
    }
};