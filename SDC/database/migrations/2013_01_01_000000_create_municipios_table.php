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
        if (Schema::hasTable('municipios')) {
            return;
        }

        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ibge', 7)->unique();
            $table->string('nome');
            $table->string('uf', 2);
            $table->string('regiao')->nullable();
            $table->string('mesorregiao')->nullable();
            $table->string('microrregiao')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['uf', 'nome']);
            $table->index('codigo_ibge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
