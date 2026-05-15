<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_crono_viagens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('crono_caminhao_id')
                ->constrained('tdap_crono_caminhoes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestampTz('data_registro');
            $table->timestampTz('data_aprovacao')->nullable();

            $table->text('obs')->nullable()->comment('Observacao do registro feito pelo prestador');
            $table->text('obs_aprovacao')->nullable()->comment('Observacao da validacao feita pelo gestor');

            $table->smallInteger('validado')->nullable()->comment('NULL=pendente, 1=aprovada, 0=rejeitada');

            $table->foreignId('user_validacao_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('crono_caminhao_id');
            $table->index('data_registro');
            $table->index('validado');
            $table->index(['crono_caminhao_id', 'validado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_crono_viagens');
    }
};
