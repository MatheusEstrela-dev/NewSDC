<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retira a tabela `cisternas`, do scaffold anterior do modulo.
 *
 * Ela modelava um dominio inventado (codigo, capacidade_litros, tipo
 * comunitaria|individual|escolar) que nao existe no legado -- o legado e
 * cadastro de beneficiario mais fiscalizacao de instalacao. O dominio novo,
 * criado em 2026_08_14_100000, nao a usa, e nenhuma FK aponta para ela.
 *
 * Migration SEPARADA de proposito, e nao um DROP dentro da migration do
 * dominio: derrubar tabela e a unica operacao aqui, o que a torna revisavel e
 * reversivel sozinha. Se aparecer dado inesperado em producao, e este arquivo
 * que se segura, sem travar a criacao do dominio.
 *
 * A guarda de contagem existe porque `cisternas` NAO deveria ter linha
 * nenhuma. Se tiver, alguem usou a tabela depois do scaffold, e a migration
 * precisa falhar em vez de apagar em silencio.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cisternas')) {
            return;
        }

        $linhas = DB::table('cisternas')->count();

        if ($linhas > 0) {
            throw new RuntimeException(
                "A tabela `cisternas` tem {$linhas} linha(s) e deveria estar vazia. "
                .'Alguem a usou depois do scaffold: conferir o dado antes de derrubar.'
            );
        }

        Schema::drop('cisternas');
    }

    /**
     * Recria a estrutura do scaffold, sem dado. Existe para o rollback nao
     * quebrar, nao porque a tabela sirva para algo.
     */
    public function down(): void
    {
        if (Schema::hasTable('cisternas')) {
            return;
        }

        Schema::create('cisternas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->string('codigo', 60)->unique();
            $table->string('nome', 255);
            $table->text('endereco')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('capacidade_litros')->nullable();
            $table->string('tipo', 20);
            $table->string('status', 20)->default('pendente');
            $table->date('data_instalacao')->nullable();
            $table->string('responsavel_nome', 255)->nullable();
            $table->string('responsavel_telefone', 20)->nullable();
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['municipio_id', 'status']);
            $table->index('legacy_id');
        });
    }
};
