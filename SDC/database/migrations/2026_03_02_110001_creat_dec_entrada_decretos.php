<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_entrada_decretos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrada_processos_id')
                ->constrained('dec_entrada_processos')
                ->onDelete('cascade');

            $table->foreignId('decreto_categoria_id')
                ->constrained('dec_decreto_categorias')
                ->onDelete('cascade');

            $table->string('observacao', 191)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_entrada_decretos');
    }
};