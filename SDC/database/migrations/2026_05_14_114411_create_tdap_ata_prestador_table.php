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
        Schema::create('tdap_ata_prestador', function (Blueprint $table) {

            $table->id();

            $table->integer('ata_id')
                ->comment('Identificador Ata');

            $table->integer('prestador_id')
                ->comment('Identificador do Prestador');

            $table->tinyInteger('status_prestador')
                ->default(0)
                ->comment('Status Prestador');

            $table->timestamp('dt_status_prestador')
                ->comment('Data desativação de Status');

            $table->timestamps();

            $table->index('prestador_id');

            $table->index('ata_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_ata_prestador');
    }
};