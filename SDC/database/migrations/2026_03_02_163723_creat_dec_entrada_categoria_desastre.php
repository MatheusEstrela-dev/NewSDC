<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_entrada_categoria_desastres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id');

            $table->foreignId('categoria_id')
                  ->constrained('dec_desastre_categorias')
                  ->onDelete('cascade');

            $table->foreignId('entrada_processo_id')
                  ->constrained('dec_entrada_processos')
                  ->onDelete('cascade');

            $table->text('descricao')->nullable();

            $table->timestamps();
            $table->softDeletes(); // deleted_at

            $table->index(
                ['municipio_id', 'categoria_id', 'entrada_processo_id'],
                'idx_entr_cat_des'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_entrada_categoria_desastres');
    }
};