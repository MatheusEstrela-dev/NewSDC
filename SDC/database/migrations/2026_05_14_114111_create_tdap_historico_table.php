<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tdap_historico', function (Blueprint $table) {

            $table->id();

            $table->dateTime('dt_historico')
                ->comment('Data Registro');

            $table->string('setor', 50)
                ->comment('Seção');

            $table->string('model', 50);

            $table->string('model_id', 50)
                ->comment('Seção');

            $table->string('texto', 255)
                ->comment('Texto do Evento/ Setor');

            $table->string('arquivo');

            $table->string('municipio');

            $table->string('nao_aplica');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_historico');
    }
};