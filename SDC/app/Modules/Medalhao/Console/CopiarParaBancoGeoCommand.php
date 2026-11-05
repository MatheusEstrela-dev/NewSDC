<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Database\CatalogoDeCopiaGeo;
use App\Modules\Medalhao\Database\CopiadorGeo;
use App\Modules\Medalhao\Database\SincronizadorReferencia;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Throwable;

class CopiarParaBancoGeoCommand extends Command
{
    /** Destino fixo: a copia roda antes de DB_GEO_CONNECTION ser ligada. */
    private const CONEXAO_DESTINO = 'pgsql_geo';

    protected $signature = 'medalhao:copiar-para-banco-geo
        {--tabela=* : Tabela(s) a copiar, com ou sem schema (padrao: todas)}
        {--lote=1000 : Linhas por INSERT}
        {--desde= : Copia so o que mudou desde este instante (delta), ex.: "2026-11-05 10:00"}
        {--verificar : Ao final compara contagem, faixa de id e hash origem x destino}
        {--dry-run : Nao escreve; mostra o que seria copiado (com --verificar, so verifica)}';

    protected $description = 'Copia o geoespacial do sdc para o banco dedicado (idempotente, em lotes)';

    public function handle(SincronizadorReferencia $referencia): int
    {
        $origem = DB::connection();
        $destino = DB::connection(self::CONEXAO_DESTINO);

        if (! $this->destinoSeparado($origem, $destino)) {
            return self::FAILURE;
        }

        $lote = max(1, (int) $this->option('lote'));
        $desde = $this->option('desde') ?: null;
        $dryRun = (bool) $this->option('dry-run');
        $verificar = (bool) $this->option('verificar');

        $tabelas = CatalogoDeCopiaGeo::filtrar((array) $this->option('tabela'));
        if ($tabelas === []) {
            $this->error('Nenhuma tabela do catalogo combina com --tabela.');

            return self::FAILURE;
        }

        $copiador = new CopiadorGeo($origem, $destino, $lote);

        // Verificar sem copiar: so com --dry-run.
        if (! ($dryRun && $verificar)) {
            $this->copiarTudo($copiador, $referencia, $origem, $destino, $tabelas, $desde, $dryRun);
        }

        return $verificar ? $this->verificarTudo($copiador, $tabelas) : self::SUCCESS;
    }

    /**
     * @param list<array{tabela: string, chave: list<string>, alteracao: string, particionada: bool}> $tabelas
     */
    private function copiarTudo(
        CopiadorGeo $copiador,
        SincronizadorReferencia $referencia,
        Connection $origem,
        Connection $destino,
        array $tabelas,
        ?string $desde,
        bool $dryRun,
    ): void {
        $this->line('Origem  : '.$this->banco($origem));
        $this->line('Destino : '.$this->banco($destino));

        // Municipios nao tem delta: sao poucos e o hash decide se reescreve.
        if (! $dryRun && $desde === null && (array) $this->option('tabela') === []) {
            $r = $referencia->sincronizar($origem, $destino);
            $this->line(sprintf('referencia.municipios: %d registro(s), %s', $r['registros'], $r['alterou'] ? 'atualizado' : 'sem mudanca'));
        }

        foreach ($tabelas as $spec) {
            if ($dryRun) {
                $this->line(sprintf('%s: %d linha(s) a copiar', $spec['tabela'], $copiador->contar($spec, $desde)));

                continue;
            }

            $total = $copiador->copiar($spec, $desde);
            $this->line(sprintf('%s: %d linha(s) copiadas', $spec['tabela'], $total));
        }

        if (! $dryRun) {
            $copiador->atualizarMatviews();
            $this->line('Matviews gold atualizadas no destino.');
        }
    }

    /**
     * @param list<array{tabela: string, chave: list<string>, alteracao: string, particionada: bool}> $tabelas
     */
    private function verificarTudo(CopiadorGeo $copiador, array $tabelas): int
    {
        $falhas = 0;

        foreach ($tabelas as $spec) {
            $v = $copiador->verificar($spec);
            $falhas += $v['ok'] ? 0 : 1;

            $this->line(sprintf(
                '%s %s: origem %s/%s..%s destino %s/%s..%s hash %s',
                $v['ok'] ? 'OK  ' : 'DIFF',
                $spec['tabela'],
                $v['origem']['contagem'], $v['origem']['id_min'], $v['origem']['id_max'],
                $v['destino']['contagem'], $v['destino']['id_min'], $v['destino']['id_max'],
                $v['ok'] ? 'igual' : 'diferente',
            ));
        }

        $falhas === 0
            ? $this->info('Verificacao: todas as tabelas conferem.')
            : $this->error("Verificacao: {$falhas} tabela(s) divergem.");

        return $falhas === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** Recusa copiar para si mesmo: seria upsert da tabela sobre ela. */
    private function destinoSeparado(Connection $origem, Connection $destino): bool
    {
        try {
            $bancoOrigem = $this->banco($origem);
            $bancoDestino = $this->banco($destino);
        } catch (Throwable $e) {
            $this->error('Nao foi possivel conectar ao destino ('.self::CONEXAO_DESTINO.'): '.$e->getMessage());

            return false;
        }

        if ($bancoOrigem === $bancoDestino) {
            $this->error("Origem e destino sao o mesmo banco ('{$bancoOrigem}'). Configure GEO_DB_* para o banco dedicado.");

            return false;
        }

        return true;
    }

    private function banco(Connection $conexao): string
    {
        // Servidor entra na identidade: o mesmo nome de banco em outro host e outro banco.
        return (string) $conexao->selectOne(
            "SELECT current_database() || '@' || COALESCE(inet_server_addr()::text, 'local') || ':' || inet_server_port() AS id"
        )->id;
    }
}
