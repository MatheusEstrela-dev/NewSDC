<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema do processo de Pedido de Material de Ajuda Humanitaria (MAH).
 *
 * Consolidado em um unico arquivo: o modulo nasce inteiro aqui, e alteracoes
 * de desenho durante a construcao editam esta migration em vez de empilhar
 * arquivos de patch.
 *
 * pedidos_ah nao tem regiao_id de proposito. Nao existe mapeamento municipio
 * para REDEC no NewSDC; o escopo regional resolve por municipios.mesorregiao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiais_ah', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('unidade_medida', 30)->default('UN');
            $table->boolean('disponivel_para_pedido')->default(true);
            $table->string('codigo_legado', 30)->nullable()
                ->comment('aju_unidade.id_unidade, ponte para o saldo do deposito legado');
            $table->timestamps();

            $table->index('disponivel_para_pedido');
            $table->index('codigo_legado');
        });

        Schema::create('parametros_ah', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('prazo_prestacao_contas_dias')->default(30);
            $table->timestamps();
        });

        Schema::create('pedidos_ah', function (Blueprint $table): void {
            $table->id();

            $table->unsignedInteger('numero');
            $table->unsignedSmallInteger('ano');

            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cobrade_id')->nullable()->constrained('dec_cobrade')->nullOnDelete();

            $table->unsignedInteger('pop_atendida')->default(0);

            $table->boolean('decreto_se_ecp_vig')->default(false);
            $table->string('tipo_decreto', 3)->nullable();
            $table->string('numero_decreto', 50)->nullable();
            $table->date('vigencia_decreto')->nullable();

            $table->text('esforcos_realizados');

            $table->string('nome_coordenador')->nullable();
            $table->string('tel_coordenador', 20)->nullable();
            $table->string('cel_coordenador', 20)->nullable();
            $table->string('email_coordenador')->nullable();

            $table->string('nome_prefeito')->nullable();
            $table->string('tel_prefeito', 20)->nullable();
            $table->string('cel_prefeito', 20)->nullable();
            $table->string('email_prefeito')->nullable();

            $table->unsignedSmallInteger('status')->default(0);

            $table->foreignId('analista_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diretor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('data_entrada_sistema');
            $table->timestamp('data_hora_envio')->nullable();
            $table->timestamp('data_aprovacao')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['numero', 'ano']);
            $table->index(['municipio_id', 'status']);
            $table->index(['ano', 'status']);
        });

        Schema::create('pedido_ah_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->nullable()->constrained('materiais_ah')->nullOnDelete();
            $table->string('codigo', 30)->nullable();
            $table->string('descricao_item');
            $table->unsignedInteger('qtd')->default(0);
            $table->unsignedInteger('qtd_familia_atendida')->default(0);
            $table->char('tipo', 1)->comment('P = solicitado pelo municipio, L = liberado pelo CEDEC');
            $table->timestamps();

            $table->index(['pedido_ah_id', 'tipo']);
        });

        Schema::create('pedido_ah_pareceres', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('data_parecer');
            $table->text('parecer');
            $table->string('situacao', 20);
            $table->string('etapa', 30);
            $table->timestamps();

            $table->index(['pedido_ah_id', 'situacao']);
        });

        Schema::create('pedido_ah_tramites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->unsignedSmallInteger('status_anterior');
            $table->unsignedSmallInteger('status_novo');
            $table->text('observacao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pedido_ah_id', 'created_at']);
        });

        Schema::create('pedido_ah_agendamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->constrained('pedidos_ah')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios')->restrictOnDelete();
            $table->date('data_retirada');
            $table->string('horario', 5)->comment('HH:MM do slot');
            $table->string('status', 20)->default('pendente');
            $table->text('motivo_recusa')->nullable();
            $table->foreignId('usuario_aprovacao_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_aprovacao')->nullable();
            $table->timestamps();

            $table->index(['pedido_ah_id', 'status']);
            $table->index(['data_retirada', 'horario']);
        });

        Schema::create('prestacoes_conta', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_ah_id')->unique()->constrained('pedidos_ah')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente');
            $table->date('data_limite')->nullable();
            $table->foreignId('homologado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('homologado_em')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('prestacao_conta_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prestacao_conta_id')->constrained('prestacoes_conta')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->nullable()->constrained('materiais_ah')->nullOnDelete();
            $table->string('codigo_material', 30)->nullable();
            $table->string('nome_material');
            $table->unsignedInteger('qtd')->default(0);
            $table->unsignedInteger('total_familia_atendida')->default(0);
            $table->timestamps();

            $table->index('prestacao_conta_id');
        });

        Schema::create('prestacao_conta_entregas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prestacao_conta_item_id')->constrained('prestacao_conta_itens')->cascadeOnDelete();
            $table->string('nome_beneficiario');
            $table->string('rg', 30)->nullable();
            $table->string('comunidade')->nullable();
            $table->unsignedInteger('qtd')->default(0);
            $table->date('data_entrega');
            $table->timestamps();

            $table->index('prestacao_conta_item_id');
        });

        DB::table('parametros_ah')->insert([
            'prazo_prestacao_contas_dias' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('prestacao_conta_entregas');
        Schema::dropIfExists('prestacao_conta_itens');
        Schema::dropIfExists('prestacoes_conta');
        Schema::dropIfExists('pedido_ah_agendamentos');
        Schema::dropIfExists('pedido_ah_tramites');
        Schema::dropIfExists('pedido_ah_pareceres');
        Schema::dropIfExists('pedido_ah_itens');
        Schema::dropIfExists('pedidos_ah');
        Schema::dropIfExists('parametros_ah');
        Schema::dropIfExists('materiais_ah');
    }
};
