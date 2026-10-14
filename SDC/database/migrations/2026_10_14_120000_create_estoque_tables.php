<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo /estoque (#32 inventario de donativos, #33 estoque e kits).
 *
 * Separado do estoque da Ajuda Humanitaria (ledger do legado) e do Inventario
 * de estacoes de trabalho: aqui ficam os donativos e insumos da CEDEC
 * (cestas, colchoes, kits de higiene) por almoxarifado, lote e validade.
 *
 * O saldo vive no lote (estoque_lotes.saldo) e so muda por movimentacao, na
 * mesma transacao: estoque_movimentacoes e o extrato que explica cada saldo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque_almoxarifados', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('tipo', 20)->default('central');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            // Capacidade em unidades, para a barra de ocupacao. Nulo = sem limite informado.
            $table->unsignedInteger('capacidade')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('estoque_produtos', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('nome', 150);
            $table->string('categoria', 20);
            $table->string('unidade_base', 10)->default('un');
            $table->string('patrimonio', 40)->nullable()->unique();
            $table->unsignedInteger('estoque_minimo')->default(0);
            $table->boolean('controla_validade')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['categoria', 'nome']);
        });

        Schema::create('estoque_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('estoque_produtos')->restrictOnDelete();
            $table->foreignId('almoxarifado_id')->constrained('estoque_almoxarifados')->restrictOnDelete();
            $table->string('codigo', 40);
            $table->date('validade')->nullable();
            $table->string('fornecedor', 150)->nullable();
            $table->string('endereco', 40)->nullable();
            $table->unsignedInteger('saldo')->default(0);
            // Quarentena: lote retido (avaria, recall, conferencia). Nao sai.
            $table->boolean('bloqueado')->default(false);
            $table->timestamps();
            $table->unique(['produto_id', 'almoxarifado_id', 'codigo']);
            $table->index(['almoxarifado_id', 'validade']);
        });

        Schema::create('estoque_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 10);
            $table->foreignId('produto_id')->constrained('estoque_produtos')->restrictOnDelete();
            $table->foreignId('lote_id')->constrained('estoque_lotes')->restrictOnDelete();
            $table->foreignId('almoxarifado_id')->constrained('estoque_almoxarifados')->restrictOnDelete();
            $table->unsignedInteger('quantidade');
            $table->unsignedInteger('saldo_apos');
            // Saida: romaneio agrupa os itens de uma mesma entrega.
            $table->string('romaneio', 20)->nullable()->index();
            $table->string('destino_tipo', 10)->nullable();
            $table->foreignId('destino_municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->string('destino_descricao', 150)->nullable();
            $table->string('documento', 60)->nullable();
            $table->text('observacao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['produto_id', 'created_at']);
        });

        Schema::create('estoque_kits', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('estoque_kit_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('estoque_kits')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('estoque_produtos')->restrictOnDelete();
            $table->unsignedInteger('quantidade');
            $table->unique(['kit_id', 'produto_id']);
        });

        DB::statement("ALTER TABLE estoque_movimentacoes ADD CONSTRAINT estoque_movimentacoes_tipo_check CHECK (tipo IN ('entrada','saida'))");
        DB::statement("ALTER TABLE estoque_movimentacoes ADD CONSTRAINT estoque_movimentacoes_quantidade_check CHECK (quantidade > 0)");
        DB::statement("ALTER TABLE estoque_kit_itens ADD CONSTRAINT estoque_kit_itens_quantidade_check CHECK (quantidade > 0)");

        DB::table('estoque_almoxarifados')->insert([
            'nome' => 'Almoxarifado Central CEDEC', 'tipo' => 'central', 'ativo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_kit_itens');
        Schema::dropIfExists('estoque_kits');
        Schema::dropIfExists('estoque_movimentacoes');
        Schema::dropIfExists('estoque_lotes');
        Schema::dropIfExists('estoque_produtos');
        Schema::dropIfExists('estoque_almoxarifados');
    }
};
