<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Uma vaga da escala: nesta data, neste tipo de turno, este plantonista.
 *
 * E a unidade que o plantonista ve no calendario, que recebe notificacao, e da
 * qual sai o botao "Assumir turno" que chama o PassagemServicoService ja
 * existente. Nao ha caminho de abertura de turno novo -- a escala apenas
 * pre-preenche o que ja existia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantao_escala_itens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('escala_id')
                ->constrained('plantao_escalas')->cascadeOnDelete();
            $table->foreignId('tipo_turno_id')
                ->constrained('plantao_tipos_turno')->restrictOnDelete();

            // Data de INICIO do turno. Turno que vira o dia (20h-08h) pertence
            // a data em que comeca, mesma convencao de plantoes.data.
            $table->date('data');

            $table->foreignId('plantonista_id')
                ->constrained('users')->cascadeOnDelete();
            // Espelho do nome no momento da escala, com posto ("Sgt Leandro").
            // Mesmo padrao de plantoes.plantonista_nome: o historico nao pode
            // mudar porque a pessoa foi promovida ou renomeada depois.
            $table->string('plantonista_nome');

            // ESCALADO -> CUMPRIDO | FALTOU | SUBSTITUIDO
            $table->string('status', 20)->default('ESCALADO');

            // Idempotencia do lembrete agendado. Sem esta marca, reinicio do
            // Octane ou sobreposicao de execucao do scheduler reenvia o aviso.
            $table->timestamp('lembrete_enviado_em')->nullable();

            $table->text('observacao')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['plantonista_id', 'data']);
            $table->index(['data', 'status']);
            $table->index('escala_id');
        });

        // Uma pessoa por vaga. Parcial para nao bloquear a vaga apos a
        // exclusao suave do item.
        //
        // ARMADILHA: soft-delete de uma escala NAO propaga para os itens pelo
        // banco (a cascata da FK so vale para delete fisico). EscalaService
        // apaga os itens junto; sem isso, criar a escala do mes de novo
        // esbarraria neste indice.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX plantao_escala_itens_vaga_unica
                ON plantao_escala_itens (data, tipo_turno_id)
                WHERE deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('plantao_escala_itens');
    }
};
