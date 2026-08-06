<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajusta a tabela `dec_cobrade` ao padrao nacional.
 *
 * O QUE MUDA:
 * 1. `descricao` e `nome` sobem de varchar(45) para varchar(255) - 21 dos 65
 *    textos oficiais passam de 45 caracteres e nao caberiam.
 * 2. `nome` recebe a denominacao oficial, casada por CODIGO. Hoje esta vazia em
 *    65 das 66 linhas, e o RAT legado exibe justamente essa coluna
 *    (LegadoRatService::baseQuery -> `c.nome as cobrade_nome`), ou seja: a tela
 *    do RAT legado mostrava o COBRADE sem nome.
 * 3. Indice unico em `codigo`, que e a identificacao real do padrao.
 *
 * O QUE NAO MUDA (de proposito):
 * - Os `id` continuam intactos. `legado_rat.cobrade_id` aponta para eles;
 *   renumerar mudaria o significado dos registros do RAT legado.
 * - `descricao` nao e sobrescrita: guarda o texto curto do cadastro legado
 *   ("blocos", "lascas") e nao ha como saber se algum relatorio depende dele.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dec_cobrade')) {
            return;
        }

        Schema::table('dec_cobrade', function (Blueprint $table) {
            $table->string('descricao', 255)->nullable()->change();
            $table->string('nome', 255)->nullable()->change();
        });

        $this->preencheNomesOficiais();

        // Unico so se os dados permitirem: a tabela e legada e um duplicado
        // faria a migration abortar no meio.
        if (! $this->temCodigoDuplicado() && ! $this->temIndice('dec_cobrade_codigo_unique')) {
            Schema::table('dec_cobrade', function (Blueprint $table) {
                $table->unique('codigo', 'dec_cobrade_codigo_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dec_cobrade')) {
            return;
        }

        if ($this->temIndice('dec_cobrade_codigo_unique')) {
            Schema::table('dec_cobrade', function (Blueprint $table) {
                $table->dropUnique('dec_cobrade_codigo_unique');
            });
        }

        // Desfaz exatamente o que o up() escreveu: volta a NULL apenas as linhas
        // cujo `nome` e o texto oficial que gravamos. Truncar em 45 (em vez de
        // reverter) deixaria nomes cortados, e o up() seguinte nao os corrigiria
        // — ele so preenche linha vazia.
        foreach ($this->mapaCodigoParaNome() as $codigo => $nome) {
            DB::table('dec_cobrade')
                ->where('codigo', $codigo)
                ->where('nome', $nome)
                ->update(['nome' => null]);
        }

        // Rede de seguranca para valores que nao vieram do up() (edicao manual):
        // sem isso o ALTER falharia na volta para varchar(45).
        DB::statement("UPDATE dec_cobrade SET nome = LEFT(nome, 45) WHERE nome IS NOT NULL AND LENGTH(nome) > 45");
        DB::statement("UPDATE dec_cobrade SET descricao = LEFT(descricao, 45) WHERE descricao IS NOT NULL AND LENGTH(descricao) > 45");

        Schema::table('dec_cobrade', function (Blueprint $table) {
            $table->string('descricao', 45)->nullable()->change();
            $table->string('nome', 45)->nullable()->change();
        });
    }

    /**
     * Grava a denominacao oficial em `nome`, casando pelo codigo.
     *
     * So preenche linha com `nome` vazio: se alguem ja ajustou um nome a mao,
     * a migration nao passa por cima.
     */
    private function preencheNomesOficiais(): void
    {
        foreach ($this->mapaCodigoParaNome() as $codigo => $nome) {
            DB::table('dec_cobrade')
                ->where('codigo', $codigo)
                ->where(function ($query) {
                    $query->whereNull('nome')->orWhere('nome', '');
                })
                ->update(['nome' => $nome]);
        }
    }

    /**
     * @return array<string, string> codigo COBRADE => denominacao oficial
     */
    private function mapaCodigoParaNome(): array
    {
        $arquivo = app_path('Enums/classificacao_desastres.php');

        if (! is_file($arquivo)) {
            return [];
        }

        $mapa = [];

        foreach ((array) include $arquivo as $item) {
            $codigo = trim((string) ($item['cobrade'] ?? ''));

            // Mesma precedencia do accessor tipo_desastre_nome do model Processo.
            $nome = trim((string) (
                $item['a_definicao']
                ?? $item['subtipo']
                ?? $item['tipo']
                ?? $item['subgrupo']
                ?? $item['grupo']
                ?? ''
            ));

            if ($codigo !== '' && $nome !== '') {
                $mapa[$codigo] = mb_substr($nome, 0, 255);
            }
        }

        return $mapa;
    }

    private function temCodigoDuplicado(): bool
    {
        return DB::table('dec_cobrade')
            ->select('codigo')
            ->whereNotNull('codigo')
            ->groupBy('codigo')
            ->havingRaw('count(*) > 1')
            ->exists();
    }

    /** Introspeccao do Schema (nao pg_indexes) para nao amarrar ao Postgres. */
    private function temIndice(string $nome): bool
    {
        foreach (Schema::getIndexes('dec_cobrade') as $indice) {
            if (($indice['name'] ?? null) === $nome) {
                return true;
            }
        }

        return false;
    }
};
