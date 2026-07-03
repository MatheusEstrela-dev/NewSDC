<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Concurrency\Concurrency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

class GlobalSearchService
{
    private const LIMIT = 7;
    private const CACHE_TTL = 60;

    public function search(string $query): array
    {
        $normalized = $this->normalize($query);

        if (mb_strlen($normalized) < 2) {
            return $this->emptyResult();
        }

        $key = 'global_search:' . md5($normalized);

        // Usa o store padrao (CACHE_DRIVER). Sem tags para compatibilidade com
        // drivers file/database; a invalidacao se da pelo TTL curto.
        return Cache::remember($key, self::CACHE_TTL, static fn () => self::runSearch($normalized));
    }

    private static function runSearch(string $query): array
    {
        if (Concurrency::databaseParallelAvailable()) {
            return Concurrency::parallel([
                'pae' => static fn (PDO $pdo) => self::searchPaeWithPdo($pdo, $query),
                'decretacoes' => static fn (PDO $pdo) => self::searchDecretacoesWithPdo($pdo, $query),
                'rat' => static fn (PDO $pdo) => self::searchRatWithPdo($pdo, $query),
                'demandas' => static fn (PDO $pdo) => self::searchDemandasWithPdo($pdo, $query),
            ]);
        }

        return Concurrency::tasks([
            'pae' => static fn () => app(self::class)->searchPae($query),
            'decretacoes' => static fn () => app(self::class)->searchDecretacoes($query),
            'rat' => static fn () => app(self::class)->searchRat($query),
            'demandas' => static fn () => app(self::class)->searchDemandas($query),
        ]);
    }

    /**
     * Isola falha por fonte: uma fonte quebrada (tabela ausente, schema
     * divergente, timeout) vira lista vazia + warning, nunca 500 na busca
     * inteira. As closures aqui sao criadas em runtime dentro do worker
     * (nunca serializadas), entao o aninhamento e seguro.
     */
    private static function fonteSegura(string $fonte, callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::warning('GlobalSearch: fonte indisponivel', [
                'fonte' => $fonte,
                'erro' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function searchPae(string $query): array
    {
        return self::fonteSegura('pae', static function () use ($query) {
            $like = '%' . $query . '%';

            $rows = DB::select(self::paeSql(), [
                'q1' => $query, 'q2' => $query, 'q3' => $query, 'q4' => $query,
                'like1' => $like, 'like2' => $like, 'like3' => $like, 'like4' => $like,
                'lim' => self::LIMIT,
            ]);

            return self::mapPaeRows($rows);
        });
    }

    public function searchDecretacoes(string $query): array
    {
        return self::fonteSegura('decretacoes', static function () use ($query) {
            $like = '%' . $query . '%';

            $rows = DB::select(self::decretacoesSql(), [
                'q1' => $query, 'q2' => $query,
                'like1' => $like, 'like2' => $like,
                'lim' => self::LIMIT,
            ]);

            return self::mapDecretacoesRows($rows);
        });
    }

    public function searchRat(string $query): array
    {
        return self::fonteSegura('rat', static function () use ($query) {
            $like = '%' . $query . '%';

            $rows = DB::select(self::ratSql(), [
                'q' => $query, 'like' => $like, 'lim' => self::LIMIT,
            ]);

            return self::mapRatRows($rows);
        });
    }

    public function searchDemandas(string $query): array
    {
        return self::fonteSegura('demandas', static function () use ($query) {
            $like = '%' . $query . '%';

            $rows = DB::select(self::demandasSql(), [
                'q1' => $query, 'q2' => $query,
                'like1' => $like, 'like2' => $like,
                'lim' => self::LIMIT,
            ]);

            return self::mapDemandasRows($rows);
        });
    }

    private static function searchPaeWithPdo(PDO $pdo, string $query): array
    {
        return self::fonteSegura('pae', static function () use ($pdo, $query) {
            $like = '%' . $query . '%';

            $rows = self::pdoSelect($pdo, self::paeSql(), [
                'q1' => $query, 'q2' => $query, 'q3' => $query, 'q4' => $query,
                'like1' => $like, 'like2' => $like, 'like3' => $like, 'like4' => $like,
                'lim' => self::LIMIT,
            ], ['lim' => PDO::PARAM_INT]);

            return self::mapPaeRows($rows);
        });
    }

    private static function searchDecretacoesWithPdo(PDO $pdo, string $query): array
    {
        return self::fonteSegura('decretacoes', static function () use ($pdo, $query) {
            $like = '%' . $query . '%';

            $rows = self::pdoSelect($pdo, self::decretacoesSql(), [
                'q1' => $query, 'q2' => $query,
                'like1' => $like, 'like2' => $like,
                'lim' => self::LIMIT,
            ], ['lim' => PDO::PARAM_INT]);

            return self::mapDecretacoesRows($rows);
        });
    }

    private static function searchRatWithPdo(PDO $pdo, string $query): array
    {
        return self::fonteSegura('rat', static function () use ($pdo, $query) {
            $like = '%' . $query . '%';

            $rows = self::pdoSelect($pdo, self::ratSql(), [
                'q' => $query,
                'like' => $like,
                'lim' => self::LIMIT,
            ], ['lim' => PDO::PARAM_INT]);

            return self::mapRatRows($rows);
        });
    }

    private static function searchDemandasWithPdo(PDO $pdo, string $query): array
    {
        return self::fonteSegura('demandas', static function () use ($pdo, $query) {
            $like = '%' . $query . '%';

            $rows = self::pdoSelect($pdo, self::demandasSql(), [
                'q1' => $query, 'q2' => $query,
                'like1' => $like, 'like2' => $like,
                'lim' => self::LIMIT,
            ], ['lim' => PDO::PARAM_INT]);

            return self::mapDemandasRows($rows);
        });
    }

    private static function paeSql(): string
    {
        return "
            SELECT
                id,
                num_protocolo,
                sei_numero,
                sigibar,
                status,
                GREATEST(
                    similarity(num_protocolo,            :q1),
                    similarity(COALESCE(sei_numero,  ''), :q2),
                    similarity(COALESCE(sigibar,      ''), :q3),
                    similarity(COALESCE(empnto_search,''), :q4)
                ) AS score
            FROM pae_protocolos
            WHERE
                num_protocolo    ILIKE :like1
                OR sei_numero    ILIKE :like2
                OR sigibar       ILIKE :like3
                OR empnto_search ILIKE :like4
            ORDER BY score DESC
            LIMIT :lim
        ";
    }

    private static function decretacoesSql(): string
    {
        return "
            SELECT
                id,
                n_protocolo_fide,
                tipo_desastre_nome,
                GREATEST(
                    similarity(COALESCE(n_protocolo_fide,   ''), :q1),
                    similarity(COALESCE(tipo_desastre_nome, ''), :q2)
                ) AS score
            FROM processos
            WHERE deleted_at IS NULL
              AND (
                    n_protocolo_fide   ILIKE :like1
                 OR tipo_desastre_nome ILIKE :like2
              )
            ORDER BY score DESC
            LIMIT :lim
        ";
    }

    private static function ratSql(): string
    {
        // Tabela real do modulo RAT e rat_ocorrencias (o schema legado 'rats'
        // nao existe neste banco); o identificador pesquisavel e numero_bos,
        // aliasado como protocolo para manter o contrato do mapRatRows.
        return "
            SELECT
                id,
                numero_bos AS protocolo,
                status,
                similarity(COALESCE(numero_bos, ''), :q) AS score
            FROM rat_ocorrencias
            WHERE deleted_at IS NULL
              AND numero_bos ILIKE :like
            ORDER BY score DESC
            LIMIT :lim
        ";
    }

    private static function demandasSql(): string
    {
        return "
            SELECT
                id,
                protocolo,
                titulo,
                status,
                GREATEST(
                    similarity(COALESCE(titulo,    ''), :q1),
                    similarity(COALESCE(protocolo, ''), :q2)
                ) AS score
            FROM tasks
            WHERE deleted_at IS NULL
              AND (titulo ILIKE :like1 OR protocolo ILIKE :like2)
            ORDER BY score DESC
            LIMIT :lim
        ";
    }

    private static function mapPaeRows(array $rows): array
    {
        return array_map(static fn ($p) => [
            'id' => $p->id,
            'title' => $p->num_protocolo,
            'subtitle' => $p->sei_numero ? 'SEI: ' . $p->sei_numero : ($p->sigibar ?? 'PAE'),
            'url' => route('pae.protocolos.index') . '?search=' . urlencode($p->num_protocolo),
            'icon' => 'document',
            'tag' => 'PAE',
        ], $rows);
    }

    private static function mapDecretacoesRows(array $rows): array
    {
        return array_map(static fn ($p) => [
            'id' => $p->id,
            'title' => $p->n_protocolo_fide ?? '—',
            'subtitle' => $p->tipo_desastre_nome ?? 'Decretacao',
            'url' => route('decretacoes.show', $p->id),
            'icon' => 'scale',
            'tag' => 'DECRETO',
        ], $rows);
    }

    private static function mapRatRows(array $rows): array
    {
        return array_map(static fn ($r) => [
            'id' => $r->id,
            'title' => $r->protocolo,
            'subtitle' => ucfirst($r->status ?? 'RAT'),
            'url' => route('rat.show', $r->id),
            'icon' => 'document',
            'tag' => 'RAT',
        ], $rows);
    }

    private static function mapDemandasRows(array $rows): array
    {
        return array_map(static fn ($t) => [
            'id' => $t->id,
            'title' => $t->titulo,
            'subtitle' => ($t->protocolo ?? '') . ' · ' . ($t->status ?? ''),
            'url' => route('demandas.show', $t->id),
            'icon' => 'checkbadge',
            'tag' => 'DEMANDA',
        ], $rows);
    }

    /**
     * @param array<string, mixed> $bindings
     * @param array<string, int> $types
     * @return array<int, object>
     */
    private static function pdoSelect(PDO $pdo, string $sql, array $bindings, array $types = []): array
    {
        $stmt = $pdo->prepare($sql);

        foreach ($bindings as $name => $value) {
            $stmt->bindValue(':'.$name, $value, $types[$name] ?? PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    private function normalize(string $query): string
    {
        // Strip leading special prefixes (#, @)
        $clean = ltrim(trim($query), '#@');

        // Remove caracteres nao-ASCII de controle e NBSP (preserva acentos latinos \xC0-\xFF)
        $clean = preg_replace('/[^\x20-\x7E\xC0-\xFF]/u', '', $clean) ?? $clean;

        // Normaliza espacos multiplos (preserva pontos e barras — importantes em numeros de protocolo)
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    private function emptyResult(): array
    {
        return ['pae' => [], 'decretacoes' => [], 'rat' => [], 'demandas' => []];
    }
}
