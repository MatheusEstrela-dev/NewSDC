<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_caminhoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestador_id')
                ->constrained('tdap_prestadores')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('placa', 8)->unique();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 50)->nullable();
            $table->string('cor', 30)->nullable();
            $table->string('ano', 4)->nullable();
            $table->decimal('capacidade_m3', 8, 2)->comment('Capacidade do tanque em m3');
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['prestador_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_caminhoes');
    }
};
