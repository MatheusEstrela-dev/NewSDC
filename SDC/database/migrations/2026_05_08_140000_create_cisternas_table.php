<?php

declare(strict_types=1);

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\ItemInstalacao;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\UnidadeItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo Cisterna — dominio portado do legado `sdc`.
 *
 * Consolidada (regra 9): esta migration substitui integralmente o scaffold
 * anterior, que criava uma tabela `cisternas` de dominio inventado
 * (codigo / capacidade_litros / tipo comunitaria|individual|escolar). O
 * legado e cadastro de beneficiario mais fiscalizacao de instalacao — ver
 * docs/superpowers/specs/2026-08-10-cisterna-migracao-legado-design.md.
 *
 * Colapsos em relacao ao legado:
 *  - as 3 tabelas de relatorio viram `cisterna_vistorias` + coluna `etapa`
 *  - ~87 colunas de checklist viram `cisterna_itens_conferidos` (polimorfica)
 *  - ~54 colunas de arquivo viram collections do Spatie MediaLibrary
 *  - `sinc_cisterna_relatorio` (89 campos, sem rota nem controller) e descartada
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->derrubarScaffold();

        $this->criarComunidades();
        $this->criarLotes();
        $this->criarOrdensServico();
        $this->criarBeneficiarios();
        $this->criarAtendimentosPipa();
        $this->criarVistorias();
        $this->criarItensConferidos();
        $this->criarNotificacoes();

        $this->criarSequenceDeNumeracao();
        $this->criarIndicesEspecificosDoPostgres();
        $this->criarCheckConstraints();
    }

    public function down(): void
    {
        // Ordem inversa das FKs.
        Schema::dropIfExists('cisterna_notificacoes');
        Schema::dropIfExists('cisterna_itens_conferidos');
        Schema::dropIfExists('cisterna_vistorias');
        Schema::dropIfExists('cisterna_atendimentos_pipa');
        Schema::dropIfExists('cisterna_beneficiarios');
        Schema::dropIfExists('cisterna_ordens_servico');
        Schema::dropIfExists('cisterna_lotes');
        Schema::dropIfExists('cisterna_comunidades');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP SEQUENCE IF EXISTS cisterna_numero_instalacao_seq');
        }
    }

    /**
     * A tabela do scaffold nunca recebeu dado real nem usuario: pode cair.
     */
    private function derrubarScaffold(): void
    {
        Schema::dropIfExists('cisternas');
    }

    private function criarComunidades(): void
    {
        Schema::create('cisterna_comunidades', function (Blueprint $table): void {
            $table->id();

            // Legado: sinc_cisterna_com guardava codmundv varchar(50) e o nome
            // do municipio duplicado. Aqui e FK de verdade.
            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnDelete();

            $table->string('nome', 70);
            $table->boolean('ativa')->default(true);

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_com.id, idempotencia do ETL');

            $table->timestampsTz();

            // Corrige o defeito C18: o legado contava por nome de comunidade
            // sem o municipio, somando homonimos de municipios distintos.
            $table->unique(['municipio_id', 'nome']);
            $table->unique('legacy_id');
        });
    }

    private function criarLotes(): void
    {
        Schema::create('cisterna_lotes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 255);
            $table->date('data')->nullable();
            $table->text('observacao')->nullable();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_lotes.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('legacy_id');
        });
    }

    private function criarOrdensServico(): void
    {
        Schema::create('cisterna_ordens_servico', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lote_id')
                ->constrained('cisterna_lotes')
                ->cascadeOnDelete();

            $table->string('nome', 255);
            $table->text('observacao')->nullable();

            // Legado: link_doc varchar. Agora e a collection documento_os
            // do MediaLibrary.
            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_ordem_servico.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('legacy_id');
            $table->index('lote_id');
        });
    }

    private function criarBeneficiarios(): void
    {
        Schema::create('cisterna_beneficiarios', function (Blueprint $table): void {
            $table->id();

            // Identificacao. Legado: varchar(14) com mascara, unicidade
            // verificada por count() em PHP antes do insert (race condition).
            // O UNIQUE NAO fica aqui: e um indice PARCIAL criado em
            // criarIndicesEspecificosDoPostgres(), porque producao tem 492
            // CPFs repetidos, 485 deles marcados como Duplicado — o legado
            // usava esse status como tombstone. Ver spec secao 4.6.5.
            $table->char('cpf', 11);
            $table->string('nome', 150);
            $table->string('telefone', 15)->nullable();
            $table->date('data_nascimento')->nullable()
                ->comment('Beneficiario deve ser maior de 18 anos');
            $table->string('cadastro_unico', 12)->nullable();

            // Localizacao. Legado duplicava o nome do municipio e da
            // comunidade como texto em quatro tabelas.
            $table->foreignId('municipio_id')
                ->constrained('municipios')
                ->cascadeOnDelete();
            $table->foreignId('comunidade_id')->nullable()
                ->constrained('cisterna_comunidades')
                ->nullOnDelete();
            $table->string('endereco', 150)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Alocacao em lote. Legado: coluna os_id solta, sem FK.
            $table->foreignId('ordem_servico_id')->nullable()
                ->constrained('cisterna_ordens_servico')
                ->nullOnDelete();

            // Os dois ciclos de vida, ortogonais entre si.
            $table->string('situacao_analise', 20)
                ->default(SituacaoAnalise::EM_EDICAO->value)
                ->comment('Analise documental. Legado: coluna `aprovado` 0..5');
            $table->string('situacao_analise_obs', 255)->nullable();
            $table->string('situacao_obra', 20)
                ->default(SituacaoObra::PROCESSAMENTO->value)
                ->comment('Execucao fisica. Legado: coluna `estado` 0..2');

            // Populada fora do sistema: nao existe calculo de ranqueamento no
            // legado (a rota do relatorio aponta para metodo inexistente).
            $table->integer('ranqueamento_ordem')->nullable();

            // Criterios sociais.
            $table->smallInteger('qtd_pessoas')->nullable();
            $table->decimal('renda', 12, 2)->nullable();
            $table->decimal('renda_per_capita', 12, 2)->nullable();
            $table->boolean('possui_deficiencia')->nullable();
            $table->boolean('possui_crianca')->nullable();
            $table->date('data_nascimento_crianca')->nullable()
                ->comment('Crianca deve ter menos de 12 anos');
            $table->boolean('possui_idoso')->nullable();
            $table->boolean('chefiada_mulher')->nullable();

            // Avaliacao tecnica do imovel. CHECK de tipo_moradia e de
            // cobertura_telhado entram depois, quando os valores distintos
            // sairem do cisterna_legado_raw.
            $table->string('tipo_moradia', 30)->nullable();
            $table->string('tipo_moradia_outro', 50)->nullable();
            $table->decimal('comprimento_telhado', 8, 2)->nullable();
            $table->decimal('largura_telhado', 8, 2)->nullable();
            $table->decimal('area_telhado', 8, 2)->nullable();
            $table->decimal('comprimento_testada', 8, 2)->nullable();
            $table->smallInteger('num_caidas_telhado')->nullable();
            $table->string('cobertura_telhado', 30)->nullable();
            $table->string('cobertura_outro', 150)->nullable();
            $table->boolean('possui_fogao_lenha')->nullable();
            $table->decimal('medida_telhado_area_fogao', 8, 2)->nullable();
            $table->decimal('testada_disp_parte_fogao', 8, 2)->nullable();
            $table->boolean('atendido_por_pipa')->nullable();

            // Responsaveis pelo cadastro em campo.
            $table->string('agente_nome', 70)->nullable();
            $table->char('agente_cpf', 11)->nullable();
            $table->string('engenheiro_nome', 150)->nullable();
            $table->string('engenheiro_crea', 20)->nullable();

            $table->text('observacoes')->nullable();

            // Dono do registro, para a trilha de acoes do sino (Rastreavel).
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            // cpf: indice unico PARCIAL, ver criarIndicesEspecificosDoPostgres().
            $table->unique('legacy_id');
            $table->index(['municipio_id', 'situacao_analise']);
            $table->index('situacao_obra');
            $table->index('ordem_servico_id');
            $table->index('comunidade_id');
        });
    }

    private function criarAtendimentosPipa(): void
    {
        Schema::create('cisterna_atendimentos_pipa', function (Blueprint $table): void {
            $table->id();

            // cascadeOnDelete: os responsaveis so existem enquanto o
            // beneficiario existir, nao tem vida propria.
            $table->foreignId('beneficiario_id')
                ->constrained('cisterna_beneficiarios')
                ->cascadeOnDelete();

            $table->string('responsavel', 20)
                ->comment('Legado: as cinco colunas respAt* de sinc_cisterna');
            $table->string('descricao', 255)->nullable()
                ->comment('Legado: outroAtendPipa, usado quando responsavel = outros');

            $table->unique(['beneficiario_id', 'responsavel']);
        });
    }

    private function criarVistorias(): void
    {
        Schema::create('cisterna_vistorias', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('beneficiario_id')
                ->constrained('cisterna_beneficiarios')
                ->cascadeOnDelete();

            $table->string('etapa', 20)
                ->comment('fornecedor -> compdec -> cedec');

            // Numero do QR Code. Legado: range(1,1800) hardcoded, escolhido
            // por array_diff contra todos os usados a cada abertura do
            // formulario (full scan + race condition).
            $table->integer('numero_instalacao')->nullable();

            $table->string('engenheiro_nome', 150)->nullable();
            $table->string('engenheiro_crea', 30)->nullable();
            $table->string('engenheiro_art', 50)->nullable();
            $table->date('data_relatorio')->nullable();
            $table->string('local_relatorio', 255)->nullable();

            // Somente etapa cedec.
            $table->string('processo_sei', 100)->nullable();
            $table->string('contrato', 100)->nullable();
            $table->string('empenho', 100)->nullable();
            $table->smallInteger('placa_obras')->nullable()
                ->comment('Legado valida required|int; semantica conferida na fase 0');

            // Snapshot do endereco no momento da vistoria: o cadastro do
            // beneficiario pode mudar depois.
            $table->string('endereco', 150)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->text('observacoes')->nullable();

            // Legado marcava a conclusao por `crea_mg` preenchido e diferente
            // de vazio, verificado com whereHas aninhado.
            $table->timestampTz('concluida_em')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // As tres tabelas de origem tem ids independentes: legacy_id
            // sozinho nao e unico, so o par com a etapa.
            $table->unsignedBigInteger('legacy_id')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['beneficiario_id', 'etapa']);
            $table->unique('numero_instalacao');
            $table->unique(['etapa', 'legacy_id']);
            $table->index(['etapa', 'concluida_em']);
        });
    }

    private function criarItensConferidos(): void
    {
        Schema::create('cisterna_itens_conferidos', function (Blueprint $table): void {
            $table->id();

            // Polimorfico: hoje aponta para cisterna_vistorias. Colapsa ~87
            // colunas espalhadas pelas 3 tabelas de relatorio do legado.
            $table->string('conferivel_type', 100);
            $table->unsignedBigInteger('conferivel_id');

            $table->string('item', 20);
            $table->boolean('conferido')->default(false);
            $table->decimal('quantidade', 10, 2)->nullable();
            $table->string('unidade', 5)->nullable();

            // Atributos que variam por item. Somente `fixacao` usa hoje:
            // {"abracadeira": "12", "bucha": "12", "parafuso": "24"} — o
            // legado tinha fix_abracadeira, fix_bucha e fix_parafuso como
            // colunas soltas do COMPDEC.
            $table->jsonb('detalhes')->nullable();

            $table->text('observacao')->nullable();

            $table->timestampsTz();

            $table->unique(['conferivel_type', 'conferivel_id', 'item'], 'cisterna_itens_conferivel_item_unq');
            $table->index(['conferivel_type', 'conferivel_id'], 'cisterna_itens_conferivel_idx');
        });
    }

    private function criarNotificacoes(): void
    {
        Schema::create('cisterna_notificacoes', function (Blueprint $table): void {
            $table->id();

            // Polimorfico: pode pender do beneficiario ou de uma vistoria.
            $table->string('notificavel_type', 100);
            $table->unsignedBigInteger('notificavel_id');

            $table->text('observacao');
            $table->boolean('respondida')->default(false);
            $table->timestampTz('respondida_em')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('sinc_cisterna_notificacoes.id');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('legacy_id');
            $table->index(
                ['notificavel_type', 'notificavel_id', 'respondida'],
                'cisterna_notif_notificavel_idx'
            );
        });
    }

    /**
     * Substitui o range(1,1800) hardcoded do legado. A alocacao passa a ser
     * atomica via nextval, com o UNIQUE da coluna como rede de seguranca.
     */
    private function criarSequenceDeNumeracao(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE SEQUENCE IF NOT EXISTS cisterna_numero_instalacao_seq START WITH 1 INCREMENT BY 1');
    }

    private function criarIndicesEspecificosDoPostgres(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // CPF unico, mas PARCIAL. Producao tem 492 CPFs repetidos em 1.003
        // linhas; 485 dessas estao marcadas aprovado=5 (Duplicado) — o legado
        // nao impedia a duplicata, ele a marcava. Um UNIQUE puro rejeitaria
        // ~511 linhas legitimas no ETL.
        //
        // Parcial resolve os dois lados: preserva os tombstones importados e
        // impede cadastro NOVO duplicado. E o tipo de indice que o Postgres
        // tem e o MySQL do legado nao tinha como expressar.
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS cisterna_beneficiarios_cpf_unq '
            .'ON cisterna_beneficiarios (cpf) '
            ."WHERE situacao_analise <> 'duplicado' AND deleted_at IS NULL"
        );

        // Busca por CPF parcial na listagem, que o unique parcial nao cobre.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_beneficiarios_cpf_idx '
            .'ON cisterna_beneficiarios (cpf)'
        );

        // Substitui whereNotNull('ranqueamento_ordem') com full scan.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_beneficiarios_ranqueamento_idx '
            .'ON cisterna_beneficiarios (ranqueamento_ordem) '
            .'WHERE ranqueamento_ordem IS NOT NULL'
        );

        // Substitui where('nome', 'like', '%termo%').
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement(
            'CREATE INDEX IF NOT EXISTS cisterna_beneficiarios_nome_trgm_idx '
            .'ON cisterna_beneficiarios USING gin (nome gin_trgm_ops)'
        );
    }

    private function criarCheckConstraints(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $checks = [
            ['cisterna_beneficiarios', 'situacao_analise', SituacaoAnalise::valores()],
            ['cisterna_beneficiarios', 'situacao_obra', SituacaoObra::valores()],
            ['cisterna_vistorias', 'etapa', EtapaVistoria::valores()],
            ['cisterna_itens_conferidos', 'item', ItemInstalacao::valores()],
            ['cisterna_atendimentos_pipa', 'responsavel', ResponsavelPipa::valores()],
        ];

        foreach ($checks as [$tabela, $coluna, $valores]) {
            $lista = implode(', ', array_map(fn (string $v): string => "'{$v}'", $valores));

            DB::statement(
                "ALTER TABLE {$tabela} ADD CONSTRAINT {$tabela}_{$coluna}_check "
                ."CHECK ({$coluna} IN ({$lista}))"
            );
        }

        // unidade e nullable: o CHECK precisa tolerar NULL.
        $unidades = implode(', ', array_map(
            fn (string $v): string => "'{$v}'",
            UnidadeItem::valores()
        ));

        DB::statement(
            'ALTER TABLE cisterna_itens_conferidos ADD CONSTRAINT cisterna_itens_conferidos_unidade_check '
            ."CHECK (unidade IS NULL OR unidade IN ({$unidades}))"
        );
    }
};
