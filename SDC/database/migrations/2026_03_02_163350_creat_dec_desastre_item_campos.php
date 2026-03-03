<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_desastre_item_campos', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment

            $table->string('tipo', 191);
            $table->string('titulo', 191);

            $table->foreignId('desastre_item_id')
                  ->constrained('dec_desastre_items')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_desastre_item_campos');
    }
};