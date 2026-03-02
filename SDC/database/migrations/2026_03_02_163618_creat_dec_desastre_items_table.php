<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_desastre_items', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment

            $table->foreignId('categoria_id')
                  ->constrained('dec_desastre_categorias')
                  ->onDelete('cascade');

            $table->string('titulo', 191);
            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_desastre_items');
    }
};