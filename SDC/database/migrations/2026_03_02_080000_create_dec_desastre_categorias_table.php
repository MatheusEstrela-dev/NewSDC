<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_desastre_categorias', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment
            
            $table->string('titulo', 191); // obrigatório
            $table->text('informacao')->nullable();
            $table->text('descricao')->nullable();

            $table->unsignedBigInteger('desastre_grupo_id')->nullable();

            $table->timestamps();
            // FK para dec_desastre_grupos nao pode ser declarada aqui pois
            // dec_desastre_grupos e criada em migration posterior (100002).
            // O index garante performance sem violar a ordem de execucao.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_desastre_categorias');
    }
};