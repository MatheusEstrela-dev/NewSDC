<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_decreto_municipios', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment
            
            $table->unsignedInteger('entrada_processos_id'); 
            $table->string('n_protocolo_fide', 100)->nullable();
            $table->unsignedInteger('municipio_id');

            $table->timestamps();

            // Se essas tabelas existirem, o ideal é já criar foreign keys:
            // $table->foreign('entrada_processos_id')->references('id')->on('entrada_processos');
            // $table->foreign('municipio_id')->references('id')->on('municipios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_decreto_municipios');
    }
};