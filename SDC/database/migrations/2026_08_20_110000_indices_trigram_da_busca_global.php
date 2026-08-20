<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indices trigram das colunas consultadas pela busca global.
 *
 * A busca filtra com `ILIKE '%termo%'` -- curinga dos DOIS lados. Sem indice
 * GIN trigram isso e varredura completa da tabela, e o custo cresce linearmente
 * com o numero de linhas: hoje passa despercebido, e vira o gargalo da caixa de
 * pesquisa conforme a base enche.
 *
 * PAE, processos, tasks e compdec_orgaos ja tinham. Faltavam estas, e a maior
 * delas e justamente a maior tabela pesquisavel do sistema.
 *
 * CONCURRENTLY nao e usado de proposito: exige rodar fora de transacao, e o
 * migrator do Laravel envolve cada migration numa. Nas tabelas atuais a criacao
 * e rapida; se alguma passar de milhoes de linhas, criar o indice a mao com
 * CONCURRENTLY antes de rodar a migration evita o bloqueio de escrita.
 */
return new class extends Migration
{
    /**
     * Tabela => [coluna => nome do indice].
     *
     * @var array<string, array<string, string>>
     */
    private const INDICES = [
        // 8.099 linhas e crescendo com o programa. `nome` ja tinha indice desde
        // a migracao do legado; o CPF nao, e e por ele que a fiscalizacao
        // procura no dia a dia.
        'cisterna_beneficiarios' => [
            'cpf' => 'cisterna_beneficiarios_cpf_trgm_idx',
        ],

        // O identificador que se digita para achar uma ocorrencia. Estava em
        // Seq Scan, confirmado por EXPLAIN.
        'rat_ocorrencias' => [
            'numero_bos' => 'rat_ocorrencias_numero_bos_trgm_idx',
        ],

        // Catalogo fixo (853 municipios de MG), entao o ganho de desempenho e
        // pequeno. Entra para a regra valer sem excecao: toda coluna consultada
        // pela busca tem indice, e assim ninguem precisa lembrar de quais sao
        // as excecoes ao acrescentar uma fonte.
        'municipios' => [
            'nome' => 'municipios_nome_trgm_idx',
            'codigo_ibge' => 'municipios_codigo_ibge_trgm_idx',
        ],
    ];

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach (self::INDICES as $tabela => $colunas) {
            if (! $this->tabelaExiste($tabela)) {
                continue;
            }

            foreach ($colunas as $coluna => $indice) {
                if (! $this->colunaExiste($tabela, $coluna)) {
                    continue;
                }

                // A EXPRESSAO indexada precisa ser identica a que a consulta
                // usa, senao o indice existe e o planejador nao o escolhe --
                // medido: com `COALESCE(nome::text,'')` no WHERE, 8.099 linhas
                // caiam em Seq Scan de 40ms mesmo com o indice no lugar.
                //
                // O projeto indexa COLUNA NUA (pae, processos, tasks,
                // compdec_orgaos, cisterna.nome), e as fontes consultam assim.
                // Só `cpf` foge: e `character`, e gin_trgm_ops nao aceita bpchar
                // sem o cast -- por isso a fonte declara `cpf::text` tambem na
                // consulta.
                $expressao = $this->exigeCast($tabela, $coluna)
                    ? "(({$coluna})::text)"
                    : $coluna;

                DB::statement(
                    "CREATE INDEX IF NOT EXISTS {$indice} ON {$tabela} USING gin ({$expressao} gin_trgm_ops)"
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDICES as $colunas) {
            foreach ($colunas as $indice) {
                DB::statement("DROP INDEX IF EXISTS {$indice}");
            }
        }
    }

    /**
     * Só colunas `character` (bpchar) precisam do cast: gin_trgm_ops nao tem
     * operador para elas. varchar e text entram nuas.
     */
    private function exigeCast(string $tabela, string $coluna): bool
    {
        return DB::selectOne(
            "SELECT data_type = 'character' AS precisa FROM information_schema.columns
             WHERE table_name = ? AND column_name = ?",
            [$tabela, $coluna],
        )?->precisa ?? false;
    }

    private function tabelaExiste(string $tabela): bool
    {
        return (bool) DB::selectOne(
            'SELECT to_regclass(?) IS NOT NULL AS existe',
            ['public.'.$tabela],
        )?->existe;
    }

    private function colunaExiste(string $tabela, string $coluna): bool
    {
        return (bool) DB::selectOne(
            'SELECT COUNT(*) > 0 AS existe FROM information_schema.columns
             WHERE table_name = ? AND column_name = ?',
            [$tabela, $coluna],
        )?->existe;
    }
};
