<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_crono_caminhoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cronograma_id')
                ->constrained('tdap_cronogramas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('caminhao_id')
                ->constrained('tdap_caminhoes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedBigInteger('comunidade_id')->nullable()
                ->comment('Referencia opcional para comunidade atendida');

            $table->decimal('agua_prevista', 12, 2)->default(0)->comment('m3 previstos');
            $table->unsignedSmallInteger('num_viagens')->default(0);
            $table->decimal('agua_entregue', 12, 2)->default(0)->comment('m3 entregues - calculado das viagens validadas');
            $table->decimal('vr_total', 12, 2)->default(0)->comment('Valor total consolidado');
            $table->unsignedTinyInteger('ordem')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['cronograma_id', 'ordem']);
            $table->index('caminhao_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_crono_caminhoes');
    }
};
