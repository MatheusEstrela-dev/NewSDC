<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GlobalSearchService
{
    private const LIMIT     = 7;
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
        return Cache::remember($key, self::CACHE_TTL, fn () => $this->runSearch($normalized));
    }

    private function runSearch(string $query): array
    {
        return [
            'pae'         => $this->searchPae($query),
            'decretacoes' => $this->searchDecretacoes($query),
            'rat'         => $this->searchRat($query),
            'demandas'    => $this->searchDemandas($query),
        ];
    }

    private function searchPae(string $query): array
    {
        $like = '%' . $query . '%';

        $rows = DB::select("
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
        ", [
            'q1' => $query, 'q2' => $query, 'q3' => $query, 'q4' => $query,
            'like1' => $like, 'like2' => $like, 'like3' => $like, 'like4' => $like,
            'lim'   => self::LIMIT,
        ]);

        return array_map(fn ($p) => [
            'id'       => $p->id,
            'title'    => $p->num_protocolo,
            'subtitle' => $p->sei_numero ? 'SEI: ' . $p->sei_numero : ($p->sigibar ?? 'PAE'),
            'url'      => route('pae.protocolos.index') . '?search=' . urlencode($p->num_protocolo),
            'icon'     => 'document',
            'tag'      => 'PAE',
        ], $rows);
    }

    private function searchDecretacoes(string $query): array
    {
        $like = '%' . $query . '%';

        $rows = DB::select("
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
        ", [
            'q1' => $query, 'q2' => $query,
            'like1' => $like, 'like2' => $like,
            'lim'   => self::LIMIT,
        ]);

        return array_map(fn ($p) => [
            'id'       => $p->id,
            'title'    => $p->n_protocolo_fide ?? '—',
            'subtitle' => $p->tipo_desastre_nome ?? 'Decretacao',
            'url'      => route('decretacoes.show', $p->id),
            'icon'     => 'scale',
            'tag'      => 'DECRETO',
        ], $rows);
    }

    private function searchRat(string $query): array
    {
        $like = '%' . $query . '%';

        $rows = DB::select("
            SELECT
                id,
                protocolo,
                status,
                similarity(COALESCE(protocolo, ''), :q) AS score
            FROM rats
            WHERE protocolo ILIKE :like
            ORDER BY score DESC
            LIMIT :lim
        ", [
            'q' => $query, 'like' => $like, 'lim' => self::LIMIT,
        ]);

        return array_map(fn ($r) => [
            'id'       => $r->id,
            'title'    => $r->protocolo,
            'subtitle' => ucfirst($r->status ?? 'RAT'),
            'url'      => route('rat.show', $r->id),
            'icon'     => 'document',
            'tag'      => 'RAT',
        ], $rows);
    }

    private function searchDemandas(string $query): array
    {
        $like = '%' . $query . '%';

        $rows = DB::select("
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
        ", [
            'q1' => $query, 'q2' => $query,
            'like1' => $like, 'like2' => $like,
            'lim'   => self::LIMIT,
        ]);

        return array_map(fn ($t) => [
            'id'       => $t->id,
            'title'    => $t->titulo,
            'subtitle' => ($t->protocolo ?? '') . ' · ' . ($t->status ?? ''),
            'url'      => route('demandas.show', $t->id),
            'icon'     => 'checkbadge',
            'tag'      => 'DEMANDA',
        ], $rows);
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
