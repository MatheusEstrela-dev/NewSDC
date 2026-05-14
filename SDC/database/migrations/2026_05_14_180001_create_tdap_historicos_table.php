<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_historicos', function (Blueprint $table) {
            $table->id();

            $table->timestampTz('data_evento')->useCurrent();
            $table->string('tipo_evento', 60)->index()
                ->comment('cronograma.criado, cronograma.ativado, cronograma.encerrado, viagem.registrada, viagem.validada, viagem.rejeitada, vistoria.aprovada, vistoria.reprovada, etc.');

            $table->string('entity_type', 60)->comment('cronograma | viagem | vistoria | etc.');
            $table->unsignedBigInteger('entity_id');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Quem disparou o evento (null = sistema/observer)');

            $table->text('obs')->nullable()->comment('Mensagem humana do evento');
            $table->jsonb('payload')->nullable()->comment('Snapshot estruturado opcional (estado antes/depois)');

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('data_evento');
            $table->index(['tipo_evento', 'data_evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_historicos');
    }
};
