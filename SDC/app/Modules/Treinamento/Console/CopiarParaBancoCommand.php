<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Console;

use App\Modules\Treinamento\Support\CopiaDeTabela;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Copia o Treinamento do sdc (schemas cadastro e treinamento, fase 1) para o
 * banco dedicado sdc_treinamento (conexao pgsql_treinamento).
 *
 * Roda ao vivo antes da virada (a copia grossa, com --verificar) e de novo na
 * janela curta, so com o delta (--desde). Idempotente por PK: repetir nao
 * duplica. As tabelas do sdc nao sao tocadas, o rollback e tirar a env.
 *
 * Limite do delta: linha apagada no sdc depois da copia nao e apagada no
 * destino; o --verificar acusa a diferenca de contagem.
 */
class CopiarParaBancoCommand extends Command
{
    /** Banco de origem nunca e destino, e estes nomes nunca recebem a copia. */
    private const DESTINOS_PROIBIDOS = ['sdc', 'forge'];

    private const CONEXAO_DESTINO = 'pgsql_treinamento';

    protected $signature = 'treinamento:copiar-para-banco
        {--tabela=* : Tabela a copiar (cidadaos, treinamentos...); padrao: todas}
        {--lote=500 : Linhas por lote}
        {--desde= : So linhas alteradas desde esta data (delta)}
        {--verificar : Compara contagem e hash entre origem e destino}
        {--dry-run : Le a origem e conta, sem escrever no destino}';

    protected $description = 'Copia o Treinamento do sdc para o banco sdc_treinamento (idempotente, com verificacao).';

    /**
     * Ordem de dependencia das FKs: pai antes de filho.
     *
     * @var array<string, string> tabela qualificada => PK
     */
    private const TABELAS = [
        'cadastro.cidadaos' => 'id',
        'cadastro.cidadao_email_verificacoes' => 'id',
        'cadastro.cidadao_password_reset_tokens' => 'email',
        'treinamento.treinamentos' => 'id',
        'treinamento.modulos' => 'id',
        'treinamento.inscricoes' => 'id',
        'treinamento.frequencias' => 'id',
        'treinamento.certificados' => 'id',
    ];

    public function handle(): int
    {
        $origem = DB::connection((string) config('database.default'));
        $destino = DB::connection(self::CONEXAO_DESTINO);

        if (! $this->destinoValido($origem, $destino)) {
            return self::FAILURE;
        }

        $copias = $this->resolverTabelas((array) $this->option('tabela'));

        if ($copias === null) {
            return self::FAILURE;
        }

        $desde = $this->resolverDesde();

        if ($desde === false) {
            return self::FAILURE;
        }

        $simular = (bool) $this->option('dry-run');
        $lote = max(1, (int) $this->option('lote'));

        foreach ($copias as $copia) {
            if (! $copia->existeEm($origem)) {
                $this->error("Origem sem a tabela {$copia->tabela()}: aplique antes a migration 2026_11_05_200000_organiza_schemas_treinamento.");

                return self::FAILURE;
            }

            if (! $copia->existeEm($destino)) {
                $this->error("Destino sem a tabela {$copia->tabela()}: rode antes as migrations de database/migrations/treinamento.");

                return self::FAILURE;
            }
        }

        foreach ($copias as $copia) {
            $total = $copia->copiar($origem, $destino, $lote, $desde, $simular);

            if (! $simular) {
                $copia->ajustarSequence($destino);
            }

            $acao = $simular ? 'a copiar' : 'copiadas';
            $this->line(sprintf('%-40s %d linhas %s', $copia->tabela(), $total, $acao));
        }

        if ($this->option('verificar')) {
            return $this->verificar($copias, $origem, $destino);
        }

        return self::SUCCESS;
    }

    /**
     * O destino tem de ser outro banco, e nunca o operacional: copiar sobre a
     * origem sobrescreveria o proprio dado com ele mesmo.
     */
    private function destinoValido(Connection $origem, Connection $destino): bool
    {
        $bancoOrigem = (string) $origem->selectOne('SELECT current_database() AS db')->db;
        $bancoDestino = (string) $destino->selectOne('SELECT current_database() AS db')->db;

        if ($bancoOrigem === $bancoDestino || in_array($bancoDestino, self::DESTINOS_PROIBIDOS, true)) {
            $this->error(
                "Recusado: a conexao de treinamento aponta para '{$bancoDestino}', a mesma base da origem ou uma base operacional. "
                .'Configure TREINAMENTO_DB_DATABASE para sdc_treinamento.'
            );

            return false;
        }

        $this->line("Origem: {$bancoOrigem}  Destino: {$bancoDestino}");

        return true;
    }

    /**
     * @param  list<string>  $pedidas
     * @return list<CopiaDeTabela>|null null quando alguma tabela pedida nao existe
     */
    private function resolverTabelas(array $pedidas): ?array
    {
        $copias = [];

        foreach (self::TABELAS as $tabela => $pk) {
            $curta = substr($tabela, (int) strpos($tabela, '.') + 1);

            if ($pedidas === [] || in_array($tabela, $pedidas, true) || in_array($curta, $pedidas, true)) {
                $copias[] = new CopiaDeTabela($tabela, $pk);
            }
        }

        $conhecidas = array_merge(array_keys(self::TABELAS), array_map(
            static fn (string $t): string => substr($t, (int) strpos($t, '.') + 1),
            array_keys(self::TABELAS),
        ));

        $desconhecidas = array_diff($pedidas, $conhecidas);

        if ($desconhecidas !== []) {
            $this->error('Tabela desconhecida: '.implode(', ', $desconhecidas));

            return null;
        }

        return $copias;
    }

    private function resolverDesde(): string|false|null
    {
        $desde = $this->option('desde');

        if ($desde === null || $desde === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $desde)->toDateTimeString();
        } catch (\Throwable) {
            $this->error("--desde invalido: {$desde}");

            return false;
        }
    }

    /**
     * @param  list<CopiaDeTabela>  $copias
     */
    private function verificar(array $copias, Connection $origem, Connection $destino): int
    {
        $divergentes = 0;

        foreach ($copias as $copia) {
            $colunas = $copia->colunas($origem);
            $a = $copia->resumo($origem, $colunas);
            $b = $copia->resumo($destino, $colunas);
            $ok = $a === $b;

            $divergentes += $ok ? 0 : 1;

            $this->line(sprintf(
                '%-40s origem=%d destino=%d hash=%s',
                $copia->tabela(),
                $a['contagem'],
                $b['contagem'],
                $ok ? 'igual' : 'DIVERGENTE',
            ));
        }

        if ($divergentes > 0) {
            $this->error("Verificacao falhou em {$divergentes} tabela(s).");

            return self::FAILURE;
        }

        $this->info('Verificacao ok: contagem e hash iguais em todas as tabelas.');

        return self::SUCCESS;
    }
}
