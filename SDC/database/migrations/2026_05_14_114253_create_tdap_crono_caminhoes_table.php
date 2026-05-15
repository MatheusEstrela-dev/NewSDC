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
        Schema::create('tdap_crono_caminhoes', function (Blueprint $table) {

            $table->id();

            $table->unsignedInteger('cronograma_id');

            $table->unsignedInteger('caminhao_id');

            $table->unsignedInteger('comunidade_id');

            $table->float('agua_prevista')
                ->default(0);

            $table->unsignedSmallInteger('num_viagens');

            $table->float('agua_entregue');

            $table->double('vr_total');

            $table->unsignedTinyInteger('ordem');

            $table->unsignedInteger('pmda_id')
                ->nullable();

            $table->unsignedInteger('pip_pmda_comun_id')
                ->nullable();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_crono_caminhoes');
    }
};