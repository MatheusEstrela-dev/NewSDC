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
        Schema::create('tdap_ata', function (Blueprint $table) {

            $table->id();

            $table->string('numero', 10)
                ->comment('Número Ata');

            $table->timestamp('dt_inicio')
                ->comment('Data Inicial');

            $table->timestamp('dt_final')
                ->comment('Data Final');

            $table->string('historico', 255)
                ->comment('Histórico');

            $table->string('planejamento', 50)
                ->nullable();

            $table->string('sei', 50)
                ->nullable();

            $table->string('siad', 50)
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_ata');
    }
};