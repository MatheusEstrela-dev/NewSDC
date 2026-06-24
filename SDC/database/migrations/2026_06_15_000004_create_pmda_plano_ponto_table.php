<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_plano_ponto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmda_plano_id')->constrained('pmda_planos')->cascadeOnDelete();
            $table->foreignId('ponto_id')->constrained('pip_pmda_ponto')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pmda_plano_id', 'ponto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_plano_ponto');
    }
};
