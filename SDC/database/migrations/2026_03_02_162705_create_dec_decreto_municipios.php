<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_decreto_municipios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrada_processos_id')
                ->constrained('dec_entrada_processos')
                ->onDelete('cascade');

            $table->string('n_protocolo_fide', 100)->nullable();

            $table->unsignedBigInteger('municipio_id');

            $table->timestamps();

            $table->index('municipio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_decreto_municipios');
    }
};