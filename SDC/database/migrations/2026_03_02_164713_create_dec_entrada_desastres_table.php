<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_entrada_desastres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id');

            $table->foreignId('item_id')
                ->constrained('dec_desastre_items')
                ->onDelete('cascade');

            $table->foreignId('item_campo_id')
                ->constrained('dec_desastre_item_campos')
                ->onDelete('cascade');

            $table->foreignId('entrada_processo_id')
                ->constrained('dec_entrada_processos')
                ->onDelete('cascade');

            $table->foreignId('entrada_categoria_desastre_id')
                ->constrained('dec_entrada_categoria_desastres')
                ->onDelete('cascade');

            $table->string('campo_titulo', 191);

            $table->text('valor')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'municipio_id',
                'item_id',
                'item_campo_id',
                'entrada_processo_id',
                'entrada_categoria_desastre_id'
            ], 'idx_entr_des');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_entrada_desastres');
    }
};