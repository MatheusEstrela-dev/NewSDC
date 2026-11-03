<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo Notas Tecnicas: repositorio de notas tecnicas e POPs em PDF, com
 * versoes e confirmacao de leitura por versao. Migration principal do modulo:
 * toda mudanca de schema dele e consolidada aqui.
 *
 * - nota_tecnica_categorias: arvore de pastas (parent_id), lida pelo painel
 *   esquerdo do workspace. As categorias iniciais nascem aqui para o deploy nao
 *   depender de seeder.
 * - notas_tecnicas: a nota em si. `codigo` (NT-AAAA-NNN) e gerado pelo sistema.
 * - nota_tecnica_versoes: cada PDF publicado. A versao corrente e a mais
 *   recente; as anteriores continuam consultaveis.
 * - nota_tecnica_leituras: "Li e estou ciente", por VERSAO -- versao nova volta
 *   a ficar pendente para quem ja tinha lido a anterior.
 *
 * `conteudo_texto`/`conteudo_busca` recebem o texto extraido do PDF por job em
 * background; ficam nulos ate a extracao rodar.
 */
return new class extends Migration
{
    private const CATEGORIAS_INICIAIS = [
        ['nome' => 'RAT', 'slug' => 'rat', 'ordem' => 10],
        ['nome' => 'TDAP / PMDA', 'slug' => 'tdap-pmda', 'ordem' => 20],
        ['nome' => 'PAE', 'slug' => 'pae', 'ordem' => 30],
        ['nome' => 'SCO', 'slug' => 'sco', 'ordem' => 40],
        ['nome' => 'Administrativo', 'slug' => 'administrativo', 'ordem' => 50],
    ];

    /** Idempotente: CREATE TEXT SEARCH CONFIGURATION nao aceita IF NOT EXISTS. */
    private const CONFIG_BUSCA_SQL = <<<'SQL'
        DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'pt_sem_acento') THEN
                CREATE TEXT SEARCH CONFIGURATION pt_sem_acento (COPY = portuguese);
                ALTER TEXT SEARCH CONFIGURATION pt_sem_acento
                    ALTER MAPPING FOR hword, hword_part, word WITH unaccent, portuguese_stem;
            END IF;
        END
        $$
        SQL;

    public function up(): void
    {
        Schema::create('nota_tecnica_categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('nota_tecnica_categorias')->nullOnDelete();
            $table->string('nome', 120);
            $table->string('slug', 140)->unique();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'ordem']);
        });

        Schema::create('notas_tecnicas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('titulo', 255);
            $table->foreignId('categoria_id')
                ->constrained('nota_tecnica_categorias')->restrictOnDelete();
            $table->text('resumo')->nullable();
            $table->string('status', 20)->default('rascunho');
            $table->date('vigencia_inicio')->nullable();
            $table->foreignId('elaborado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('rotas_relacionadas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['categoria_id', 'status']);
            $table->index('status');
        });

        Schema::create('nota_tecnica_versoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_tecnica_id')
                ->constrained('notas_tecnicas')->cascadeOnDelete();
            $table->string('numero', 20);
            $table->string('arquivo_path', 500);
            $table->string('arquivo_nome', 255);
            $table->unsignedBigInteger('arquivo_tamanho')->default(0);
            $table->text('changelog')->nullable();
            $table->timestamp('publicada_em')->nullable();
            $table->foreignId('publicada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('conteudo_texto')->nullable();
            $table->timestamps();

            $table->unique(['nota_tecnica_id', 'numero']);
            $table->index(['nota_tecnica_id', 'created_at']);
        });

        Schema::create('nota_tecnica_leituras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('nota_tecnica_versao_id')
                ->constrained('nota_tecnica_versoes')->cascadeOnDelete();
            $table->timestamp('lida_em');

            $table->unique(['user_id', 'nota_tecnica_versao_id']);
            $table->index('nota_tecnica_versao_id');
        });

        // Favoritos: marca pessoal do usuario sobre a nota (e nao sobre a versao).
        Schema::create('nota_tecnica_favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('nota_tecnica_id')->constrained('notas_tecnicas')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'nota_tecnica_id']);
        });

        // Recentes: uma linha por (usuario, nota) com o ultimo acesso; reabrir so
        // atualiza `acessado_em`, entao a tabela nao cresce a cada clique.
        Schema::create('nota_tecnica_acessos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('nota_tecnica_id')->constrained('notas_tecnicas')->cascadeOnDelete();
            $table->timestamp('acessado_em');

            $table->unique(['user_id', 'nota_tecnica_id']);
            $table->index(['user_id', 'acessado_em']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

            // Busca do workspace: ILIKE '%termo%' em titulo e codigo.
            DB::statement('CREATE INDEX notas_tecnicas_titulo_trgm_idx ON notas_tecnicas USING gin (titulo gin_trgm_ops)');
            DB::statement('CREATE INDEX notas_tecnicas_codigo_trgm_idx ON notas_tecnicas USING gin (codigo gin_trgm_ops)');

            // Busca no conteudo do PDF. Coluna comum (nao GENERATED): o job de
            // extracao grava o vetor explicitamente.
            DB::statement('ALTER TABLE nota_tecnica_versoes ADD COLUMN conteudo_busca tsvector');
            DB::statement('CREATE INDEX nota_tecnica_versoes_conteudo_busca_idx ON nota_tecnica_versoes USING gin (conteudo_busca)');

            // Configuracao de texto `pt_sem_acento`: portugues com o acento tirado
            // DENTRO do dicionario. Indice, consulta e ts_headline usam a mesma
            // regra -- "inundacao" casa e destaca "inundação" no texto original.
            // Com unaccent() so em volta do vetor, o destaque nao casava.
            DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
            DB::statement(self::CONFIG_BUSCA_SQL);
        }

        $agora = now();
        DB::table('nota_tecnica_categorias')->insert(array_map(
            static fn (array $categoria): array => $categoria + ['created_at' => $agora, 'updated_at' => $agora],
            self::CATEGORIAS_INICIAIS,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_tecnica_acessos');
        Schema::dropIfExists('nota_tecnica_favoritos');
        Schema::dropIfExists('nota_tecnica_leituras');
        Schema::dropIfExists('nota_tecnica_versoes');
        Schema::dropIfExists('notas_tecnicas');
        Schema::dropIfExists('nota_tecnica_categorias');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TEXT SEARCH CONFIGURATION IF EXISTS pt_sem_acento');
        }
    }
};
