<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nucleo de estoque do modulo Ajuda Humanitaria.
 *
 * Consolidado em um unico arquivo: o schema nasce inteiro aqui, e alteracoes de
 * desenho durante a construcao editam esta migration em vez de empilhar patch.
 *
 * A inversao em relacao ao legado: aju_estoque.saldo era coluna editavel por
 * cinco caminhos distintos, em MyISAM, sem transacao e sem trilha. Aqui a
 * verdade e o ledger ajuda_h_estoque_movimentos (append-only) e
 * ajuda_h_estoque_saldos e projecao, protegida por CHECK (saldo >= 0), o que
 * torna saldo negativo impossivel por construcao em vez de por validacao na
 * aplicacao. aju_estoque_anterior nao tem equivalente: o historico vira
 * consulta ao ledger por janela de tempo, em qualquer granularidade.
 *
 * O catalogo de material continua em materiais_ah, que ja existe e ja carrega
 * codigo_legado apontando para aju_unidade.id_unidade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajuda_h_fontes_recurso', function (Blueprint $table): void {
            $table->id();
            $table->string('nome')->unique();
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_fonte.id');
            $table->timestamps();
        });

        Schema::create('ajuda_h_fornecedores', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('cpf_cnpj', 20)->nullable()->unique();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->text('endereco')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_fornecedores.id (dbsdc) ou aju_cfornecedor.id_fornecedor (gestaocedec)');
            $table->timestamps();

            // O Postgres nao indexa a coluna filha de uma FK automaticamente.
            // Sem este indice, apagar ou renumerar um municipio varre a tabela
            // inteira para validar a restricao.
            $table->index('municipio_id', 'ajuda_h_fornecedores_municipio_idx');
        });

        Schema::create('ajuda_h_depositos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('abreviacao', 10)->unique();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            // Sem orgao_id: nao existe tabela "orgaos" neste banco (a que existe
            // e compdec_orgaos, de outro dominio) e aju_deposito nao tem o
            // conceito, traz regiao e id_rpm.
            $table->geography('ponto', 'point', 4326)->nullable()
                ->comment('Localizacao, para roteirizar transferencia e achar deposito mais proximo');
            $table->text('endereco')->nullable();
            $table->boolean('ativo')->default(true);
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_deposito.id_deposito');
            $table->timestamps();

            $table->index('ativo', 'ajuda_h_depositos_ativo_idx');
            $table->index('municipio_id', 'ajuda_h_depositos_municipio_idx');
        });

        Schema::create('ajuda_h_estoque_movimentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_ah_id')->constrained('materiais_ah')->restrictOnDelete();
            $table->foreignId('deposito_id')->constrained('ajuda_h_depositos')->restrictOnDelete();
            $table->decimal('quantidade', 14, 3)
                ->comment('O sinal define o sentido: positivo entra, negativo sai');
            $table->string('tipo', 20)
                ->comment('ABERTURA|ENTRADA|SAIDA|BAIXA|TRANSF_SAIDA|TRANSF_ENTRADA|AJUSTE');
            $table->string('origem_tipo', 40)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->timestampTz('ocorrido_em');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            // Mesmo retrato de cargo das liberacoes. O ledger e a tabela mais
            // sensivel a auditoria do modulo: quem lancou importa tanto quanto
            // em que papel lancou.
            $table->foreignId('cargo_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->jsonb('payload_legado')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['material_ah_id', 'deposito_id', 'ocorrido_em'], 'ajuda_h_mov_material_dep_data_idx');
            $table->index(['origem_tipo', 'origem_id'], 'ajuda_h_mov_origem_idx');
        });

        DB::statement(
            'ALTER TABLE ajuda_h_estoque_movimentos
                ADD CONSTRAINT ajuda_h_mov_quantidade_ck CHECK (quantidade <> 0)'
        );
        DB::statement(
            'ALTER TABLE ajuda_h_estoque_movimentos
                ADD CONSTRAINT ajuda_h_mov_origem_ck
                CHECK ((origem_tipo IS NULL) = (origem_id IS NULL))'
        );
        // BRIN: a tabela e append-only e sempre consultada por janela de tempo.
        // Ocupa uma fracao do espaco de um btree para o mesmo ganho aqui.
        DB::statement(
            'CREATE INDEX ajuda_h_mov_ocorrido_brin
                ON ajuda_h_estoque_movimentos USING brin (ocorrido_em)'
        );

        Schema::create('ajuda_h_estoque_saldos', function (Blueprint $table): void {
            $table->foreignId('material_ah_id')->constrained('materiais_ah')->restrictOnDelete();
            $table->foreignId('deposito_id')->constrained('ajuda_h_depositos')->restrictOnDelete();
            $table->decimal('saldo', 14, 3)->default(0);
            $table->timestampTz('atualizado_em')->useCurrent();

            $table->primary(['material_ah_id', 'deposito_id']);
        });

        DB::statement(
            'ALTER TABLE ajuda_h_estoque_saldos
                ADD CONSTRAINT ajuda_h_saldos_nao_negativo_ck CHECK (saldo >= 0)'
        );

        Schema::create('ajuda_h_entradas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deposito_id')->constrained('ajuda_h_depositos')->restrictOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('ajuda_h_fornecedores')->nullOnDelete();
            $table->foreignId('fonte_recurso_id')->nullable()->constrained('ajuda_h_fontes_recurso')->nullOnDelete();
            $table->string('nota_fiscal', 70)->nullable();
            $table->timestampTz('recebido_em');
            $table->boolean('cancelado')->default(false);
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable()
                ->comment('aju_produto.obs, que carrega justificativa de correcao de saldo');
            // aju_produto.origem e texto livre que mistura fonte de recurso
            // (CAMPANHA DOACAO, LBV) com tipo de movimento (Transferencia entre
            // Depositos, Correcao Manual de Saldo), em tres grafias diferentes.
            // So a parte que casa com aju_fonte vira fonte_recurso_id; o resto
            // fica aqui em vez de virar cadastro inventado.
            $table->jsonb('payload_legado')->nullable();
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_produto.id_produto (apesar do nome, e registro de entrada)');
            $table->timestamps();

            $table->index(['deposito_id', 'recebido_em'], 'ajuda_h_entradas_dep_data_idx');
        });

        Schema::create('ajuda_h_entrada_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('entrada_id')->constrained('ajuda_h_entradas')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->constrained('materiais_ah')->restrictOnDelete();
            $table->decimal('qtd', 14, 3);
            $table->decimal('valor_unitario', 16, 2)->nullable();
            // O legado gravava val_total a mao em tres tabelas distintas, com
            // tres oportunidades de divergir. Aqui o banco calcula.
            $table->decimal('valor_total', 16, 2)->nullable()->storedAs('qtd * valor_unitario');
            $table->date('data_validade')->nullable();

            $table->index('entrada_id', 'ajuda_h_entrada_itens_entrada_idx');
        });

        Schema::create('ajuda_h_transferencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('deposito_origem_id')->constrained('ajuda_h_depositos')->restrictOnDelete();
            $table->foreignId('deposito_destino_id')->constrained('ajuda_h_depositos')->restrictOnDelete();
            $table->string('motorista', 70)->nullable();
            $table->string('veiculo', 45)->nullable();
            $table->string('placa', 10)->nullable();
            $table->timestampTz('saiu_em')->nullable();
            $table->timestampTz('chegou_em')->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->string('responsavel', 70)->nullable();
            $table->text('observacao')->nullable();
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_transferencia.id_transferencia');
            $table->timestamps();

            $table->index(['status', 'saiu_em'], 'ajuda_h_transf_status_idx');
        });

        DB::statement(
            'ALTER TABLE ajuda_h_transferencias
                ADD CONSTRAINT ajuda_h_transf_depositos_distintos_ck
                CHECK (deposito_origem_id <> deposito_destino_id)'
        );

        Schema::create('ajuda_h_transferencia_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transferencia_id')->constrained('ajuda_h_transferencias')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->constrained('materiais_ah')->restrictOnDelete();
            $table->decimal('qtd', 14, 3);
            $table->unsignedSmallInteger('status')->default(0);

            $table->index('transferencia_id', 'ajuda_h_transf_itens_transf_idx');
        });

        Schema::create('ajuda_h_liberacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->restrictOnDelete();
            $table->foreignId('deposito_id')->constrained('ajuda_h_depositos')->restrictOnDelete();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->nullOnDelete();
            // Cargo exercido no momento da liberacao, nao o cargo atual da
            // pessoa. Cargo muda com o tempo: sem este retrato, um relatorio de
            // 2027 atribuiria uma liberacao de 2020 ao papel que o solicitante
            // ocupa hoje. Nulo nas 3.582 linhas vindas do legado, onde nem o
            // solicitante e recuperavel.
            $table->foreignId('cargo_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->text('beneficiario')->nullable();
            $table->date('data_libera');
            $table->date('data_limite')->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->text('observacao')->nullable();
            $table->timestampTz('cancelado_em')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            // Promovida de payload_legado->evento: e filtro de consulta da API
            // de liberacoes, e filtro sobre jsonb nao usa indice.
            $table->string('evento', 40)->nullable();
            // Colunas do legado sem consumidor conhecido (resp_receb_ci,
            // resp_receb_veiculo, resp_receb_placa, hora_libera, entrega).
            // Ficam aqui ate alguem provar que sao usadas; se em seis meses
            // ninguem consultar, a coluna cai inteira.
            $table->jsonb('payload_legado')->nullable();
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_liberacao.id_liberacao');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['municipio_id', 'status'], 'ajuda_h_liberacoes_mun_status_idx');
            $table->index(['deposito_id', 'data_libera'], 'ajuda_h_liberacoes_dep_data_idx');
            $table->index(['evento', 'data_libera'], 'ajuda_h_liberacoes_evento_data_idx');
        });

        Schema::create('ajuda_h_liberacao_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liberacao_id')->constrained('ajuda_h_liberacoes')->cascadeOnDelete();
            $table->foreignId('material_ah_id')->constrained('materiais_ah')->restrictOnDelete();
            $table->decimal('qtd', 14, 3);
            $table->unsignedSmallInteger('status')->default(0);
            $table->string('codigo_legado', 30)->nullable()->unique()
                ->comment('aju_item.id_item');

            // Composto: o contrato plano do CEDEC filtra por status dentro do
            // join por liberacao_id.
            $table->index(['liberacao_id', 'status'], 'ajuda_h_lib_itens_liberacao_idx');
        });

        Schema::create('ajuda_h_liberacao_recibos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('liberacao_id')->constrained('ajuda_h_liberacoes')->cascadeOnDelete();
            $table->date('pago_em')->nullable();
            $table->string('n_documento', 45)->nullable();
            $table->unsignedInteger('n_recibo')->nullable();
            $table->string('responsavel_recebimento', 70)->nullable();
            $table->string('cpf_responsavel', 20)->nullable();
            $table->string('placa_veiculo', 10)->nullable();
            $table->unsignedSmallInteger('status')->default(0);
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index(['liberacao_id', 'pago_em'], 'ajuda_h_recibos_liberacao_idx');
        });

        // materiais_ah.codigo_legado ja existia como indice simples. O ETL usa
        // ON CONFLICT sobre ele, o que exige unicidade. Postgres permite
        // multiplos NULL sob UNIQUE, entao material sem correspondente no
        // legado continua valido.
        DB::statement(
            'ALTER TABLE materiais_ah
                ADD CONSTRAINT materiais_ah_codigo_legado_unique UNIQUE (codigo_legado)'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE materiais_ah DROP CONSTRAINT IF EXISTS materiais_ah_codigo_legado_unique');

        Schema::dropIfExists('ajuda_h_liberacao_recibos');
        Schema::dropIfExists('ajuda_h_liberacao_itens');
        Schema::dropIfExists('ajuda_h_liberacoes');
        Schema::dropIfExists('ajuda_h_transferencia_itens');
        Schema::dropIfExists('ajuda_h_transferencias');
        Schema::dropIfExists('ajuda_h_entrada_itens');
        Schema::dropIfExists('ajuda_h_entradas');
        Schema::dropIfExists('ajuda_h_estoque_saldos');
        Schema::dropIfExists('ajuda_h_estoque_movimentos');
        Schema::dropIfExists('ajuda_h_depositos');
        Schema::dropIfExists('ajuda_h_fornecedores');
        Schema::dropIfExists('ajuda_h_fontes_recurso');
    }
};
