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

            $table->unsignedInteger('desastre_grupo_id')->nullable();

            $table->timestamps();

            // Se existir a tabela desastre_grupos, ideal adicionar foreign key:
            /*
            $table->foreign('desastre_grupo_id')
                  ->references('id')
                  ->on('desastre_grupos')
                  ->nullOnDelete();
            */
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_desastre_categorias');
    }
};