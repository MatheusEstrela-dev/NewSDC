<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Medalhao\Database\CatalogoDeCopiaGeo;
use App\Modules\Medalhao\Database\CopiadorGeo;
use App\Modules\Treinamento\Support\CopiaDeTabela;
use App\Support\Database\ConexaoGeo;
use App\Support\Database\ConexaoSeparada;
use App\Support\Database\Geo\DominioGeo;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Remove do sdc os schemas que ja vivem nos bancos dedicados (geo e treinamento).
 *
 * Destrutivo, entao so age quando o banco dedicado esta em uso (chave ligada),
 * e outro banco que nao o sdc e tem contagem e hash iguais em todas as
 * tabelas. Qualquer duvida: nao apaga nada e diz o motivo. O rollback da
 * virada (desligar a chave) deixa de existir para o dominio removido.
 */
class RemoverSchemasMigradosCommand extends Command
{
    private const GEO = 'geo';

    private const TREINAMENTO = 'treinamento';

    private const CHAVE_TREINAMENTO = 'treinamento_connection';

    protected $signature = 'db:remover-schemas-migrados
        {--dominio=* : geo e/ou treinamento (padrao: ambos)}
        {--dry-run : So relata o que seria removido}';

    protected $description = 'Remove do sdc os schemas de geo e treinamento ja migrados para os bancos dedicados (so se a verificacao bater).';

    public function handle(): int
    {
        $dominios = $this->resolverDominios((array) $this->option('dominio'));

        if ($dominios === null) {
            return self::FAILURE;
        }

        $falhou = false;

        foreach ($dominios as $dominio) {
            $falhou = ! $this->processar($dominio) || $falhou;
        }

        return $falhou ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  list<string>  $pedidos
     * @return list<string>|null
     */
    private function resolverDominios(array $pedidos): ?array
    {
        $validos = [self::GEO, self::TREINAMENTO];
        $invalidos = array_diff($pedidos, $validos);

        if ($invalidos !== []) {
            $this->error('Dominio desconhecido: '.implode(', ', $invalidos).'. Validos: '.implode(', ', $validos));

            return null;
        }

        return $pedidos === [] ? $validos : array_values(array_unique($pedidos));
    }

    private function processar(string $dominio): bool
    {
        $origem = DB::connection((string) config('database.default'));
        $schemas = $this->schemas($dominio);
        $bloqueios = [];

        $destino = $this->destino($dominio, $origem, $bloqueios);

        if ($destino !== null) {
            $bloqueios = $this->verificar($dominio, $origem, $destino);
        }

        if ($bloqueios !== []) {
            $this->error("[{$dominio}] nada removido:");

            foreach ($bloqueios as $motivo) {
                $this->line('  - '.$motivo);
            }

            return false;
        }

        if ($this->option('dry-run')) {
            $this->info("[{$dominio}] dry-run: verificacao ok, removeria ".implode(', ', $schemas));

            return true;
        }

        $this->remover($origem, $schemas);
        $this->info("[{$dominio}] schemas removidos do sdc: ".implode(', ', $schemas));

        return true;
    }

    /**
     * Conexao dedicada, ou null com o motivo em $bloqueios.
     *
     * @param  list<string>  $bloqueios
     */
    private function destino(string $dominio, Connection $origem, array &$bloqueios): ?Connection
    {
        $treinamento = ConexaoSeparada::de(self::CHAVE_TREINAMENTO);

        if (! ($dominio === self::GEO ? ConexaoGeo::separada() : $treinamento->separada())) {
            $bloqueios[] = 'a chave do dominio esta desligada (o sdc ainda e a fonte de leitura)';

            return null;
        }

        try {
            $destino = $dominio === self::GEO ? ConexaoGeo::conexao() : $treinamento->conexao();
            $bancoDestino = $this->banco($destino);
        } catch (Throwable $e) {
            $bloqueios[] = 'nao foi possivel conectar ao banco dedicado: '.$e->getMessage();

            return null;
        }

        if ($bancoDestino === $this->banco($origem)) {
            $bloqueios[] = "a conexao dedicada aponta para o proprio sdc ('{$bancoDestino}')";

            return null;
        }

        return $destino;
    }

    /**
     * @return list<string> motivos que impedem a remocao (vazio = pode remover)
     */
    private function verificar(string $dominio, Connection $origem, Connection $destino): array
    {
        try {
            return $dominio === self::GEO
                ? $this->bloqueiosGeo($origem, $destino)
                : $this->bloqueiosTreinamento($origem, $destino);
        } catch (Throwable $e) {
            return ['verificacao falhou: '.$e->getMessage()];
        }
    }

    /** @return list<string> */
    private function bloqueiosGeo(Connection $origem, Connection $destino): array
    {
        $copiador = new CopiadorGeo($origem, $destino);
        $bloqueios = array_map(
            static fn (string $t): string => "{$t} diverge entre sdc e banco dedicado",
            $copiador->divergentes(CatalogoDeCopiaGeo::todas()),
        );

        // referencia.municipios e espelho de public.municipios do sdc.
        $esperado = (int) $origem->table('public.municipios')->count();
        $atual = (int) $destino->table(DominioGeo::REFERENCIA.'.municipios')->count();

        if ($esperado !== $atual) {
            $bloqueios[] = "referencia.municipios tem {$atual} linha(s) no destino e public.municipios tem {$esperado}";
        }

        return $bloqueios;
    }

    /** @return list<string> */
    private function bloqueiosTreinamento(Connection $origem, Connection $destino): array
    {
        $bloqueios = [];

        foreach (CopiaDeTabela::dosSchemas($origem, $this->schemas(self::TREINAMENTO)) as $copia) {
            if (! $copia->existeEm($destino)) {
                $bloqueios[] = "{$copia->tabela()} nao existe no banco dedicado";
            } elseif ($copia->divergente($origem, $destino)) {
                $bloqueios[] = "{$copia->tabela()} diverge entre sdc e banco dedicado";
            }
        }

        return $bloqueios;
    }

    /**
     * Lista fechada: nunca public, log nem schema fora do dominio.
     *
     * @return list<string>
     */
    private function schemas(string $dominio): array
    {
        if ($dominio === self::TREINAMENTO) {
            return ['cadastro', 'treinamento'];
        }

        $schemas = [];

        foreach (DominioGeo::cases() as $caso) {
            array_push($schemas, ...$caso->schemas());
        }

        return [...$schemas, DominioGeo::REFERENCIA];
    }

    /** @param  list<string>  $schemas */
    private function remover(Connection $origem, array $schemas): void
    {
        $origem->transaction(function () use ($origem, $schemas): void {
            foreach ($schemas as $schema) {
                $origem->statement('DROP SCHEMA IF EXISTS "'.$schema.'" CASCADE');
            }
        });
    }

    private function banco(Connection $conexao): string
    {
        return (string) $conexao->selectOne('SELECT current_database() AS db')->db;
    }
}
