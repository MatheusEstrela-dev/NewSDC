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
        Schema::createIfNotExists('dec_cobrade', function (Blueprint $table) {
           $table->increments('id')->comment('Identificador do Cobrade');
            $table->string('codigo', 45)->nullable()->comment('codigo do cobrade');
            $table->string('descricao', 45)->nullable()->comment('Descrição do cobrade');
            $table->string('nome', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dec_cobrade');
    }
};
