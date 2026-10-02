<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_ti_categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('inventario_ti_estacoes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->string('ponto_rede', 120)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventario_ti_equipamentos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 255);
            $table->string('patrimonio', 100)->unique();
            $table->string('numero_serie', 150)->nullable();
            $table->string('ramal', 30)->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('inventario_ti_categorias')->restrictOnDelete();
            $table->foreignId('estacao_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unidade', 150)->nullable();
            $table->string('diretoria', 150)->nullable();
            $table->string('situacao', 30)->default('disponivel')->index();
            $table->boolean('emprestavel')->default(false);
            $table->unsignedInteger('quantidade')->default(1);
            $table->string('foto_path')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['categoria_id', 'situacao']);
            $table->index(['estacao_id', 'situacao']);
        });

        // Cabecalho do lote de remanejamento. Vem antes de movimentacoes porque
        // movimentacoes.lote_id aponta para ele.
        Schema::create('inventario_ti_remanejamentos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->string('status', 20)->default('ativo')->index();
            $table->foreignId('demanda_id')->nullable()->unique()->constrained('tasks')->nullOnDelete();
            $table->timestamp('seplag_enviado_em')->nullable();
            $table->foreignId('seplag_enviado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('seplag_envios')->default(0);
            $table->timestamp('desfeito_em')->nullable();
            $table->foreignId('desfeito_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // Unidade do lote: uma pessoa com origem, destino e o que leva junto.
        Schema::create('inventario_ti_remanejamento_pessoas', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('remanejamento_id')->constrained('inventario_ti_remanejamentos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('estacao_origem_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('estacao_destino_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('estacao_destino_usuario_anterior_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('estacao_origem_ficou_vazia')->default(false);
            $table->string('condicao_destino', 60)->nullable();
            $table->timestamps();
            $table->unique(['remanejamento_id', 'usuario_id']);
        });

        Schema::create('inventario_ti_movimentacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipamento_id')->constrained('inventario_ti_equipamentos')->restrictOnDelete();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_origem_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_destino_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('estacao_origem_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('estacao_destino_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignUuid('lote_id')->nullable()->constrained('inventario_ti_remanejamentos')->nullOnDelete();
            $table->foreignId('remanejamento_pessoa_id')->nullable()->constrained('inventario_ti_remanejamento_pessoas')->nullOnDelete();
            $table->string('situacao_origem', 30)->nullable();
            $table->string('tipo', 30);
            $table->string('status', 30)->default('ativo');
            $table->unsignedInteger('quantidade')->default(1);
            $table->timestamp('data_saida');
            $table->timestamp('data_prevista_devolucao')->nullable();
            $table->timestamp('data_devolucao')->nullable();
            $table->string('retirante_nome')->nullable();
            $table->string('retirante_cpf', 20)->nullable();
            $table->string('retirante_contato')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->index(['equipamento_id', 'status']);
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_ti_movimentacoes');
        Schema::dropIfExists('inventario_ti_remanejamento_pessoas');
        Schema::dropIfExists('inventario_ti_remanejamentos');
        Schema::dropIfExists('inventario_ti_equipamentos');
        Schema::dropIfExists('inventario_ti_estacoes');
        Schema::dropIfExists('inventario_ti_categorias');
    }
};
