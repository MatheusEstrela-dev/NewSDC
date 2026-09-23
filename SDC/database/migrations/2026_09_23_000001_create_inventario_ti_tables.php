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

        Schema::create('inventario_ti_movimentacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equipamento_id')->constrained('inventario_ti_equipamentos')->restrictOnDelete();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_origem_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_destino_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('estacao_origem_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->foreignId('estacao_destino_id')->nullable()->constrained('inventario_ti_estacoes')->nullOnDelete();
            $table->uuid('lote_id')->nullable()->index();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_ti_movimentacoes');
        Schema::dropIfExists('inventario_ti_equipamentos');
        Schema::dropIfExists('inventario_ti_estacoes');
        Schema::dropIfExists('inventario_ti_categorias');
    }
};
