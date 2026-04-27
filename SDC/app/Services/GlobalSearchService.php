<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Rat\Models\Rat;
use Illuminate\Support\Facades\Cache;

class GlobalSearchService
{
    private const LIMIT     = 5;
    private const CACHE_TTL = 60;

    public function search(string $query): array
    {
        $normalized = $this->normalize($query);

        if (mb_strlen($normalized) < 2) {
            return $this->emptyResult();
        }

        $key = 'global_search:' . md5($normalized);

        return Cache::store('redis')
            ->tags(['global_search'])
            ->remember($key, self::CACHE_TTL, fn () => $this->runSearch($normalized));
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
        return PaeProtocolo::select(['id', 'num_protocolo', 'sei_numero', 'sigibar', 'status'])
            ->where(function ($q) use ($query) {
                $q->whereRaw('num_protocolo ILIKE ?', ["%{$query}%"])
                  ->orWhereRaw('sei_numero ILIKE ?', ["%{$query}%"])
                  ->orWhereRaw('sigibar ILIKE ?', ["%{$query}%"])
                  ->orWhereRaw('empnto_search ILIKE ?', ["%{$query}%"]);
            })
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->num_protocolo,
                'subtitle' => $p->sei_numero ? 'SEI: ' . $p->sei_numero : ($p->sigibar ?? 'PAE'),
                'url'      => route('pae.protocolos.index') . '?search=' . urlencode($p->num_protocolo),
                'icon'     => 'document',
                'tag'      => 'PAE',
            ])
            ->toArray();
    }

    private function searchDecretacoes(string $query): array
    {
        return Processo::without(['municipios', 'desastres'])
            ->select(['id', 'n_protocolo_fide', 'tipo_desastre_nome'])
            ->where(function ($q) use ($query) {
                $q->whereRaw('n_protocolo_fide ILIKE ?', ["%{$query}%"])
                  ->orWhereRaw('tipo_desastre_nome ILIKE ?', ["%{$query}%"]);
            })
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->n_protocolo_fide,
                'subtitle' => $p->tipo_desastre_nome ?? 'Decretacao',
                'url'      => route('decretacoes.index') . '?search=' . urlencode($p->n_protocolo_fide),
                'icon'     => 'scale',
                'tag'      => 'DECRETO',
            ])
            ->toArray();
    }

    private function searchRat(string $query): array
    {
        return Rat::select(['id', 'protocolo', 'status'])
            ->whereRaw('protocolo ILIKE ?', ["%{$query}%"])
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($r) => [
                'id'       => $r->id,
                'title'    => $r->protocolo,
                'subtitle' => ucfirst($r->status ?? 'RAT'),
                'url'      => route('rat.show', $r->id),
                'icon'     => 'document',
                'tag'      => 'RAT',
            ])
            ->toArray();
    }

    private function searchDemandas(string $query): array
    {
        return Task::select(['id', 'protocolo', 'titulo', 'status'])
            ->where(function ($q) use ($query) {
                $q->whereRaw('titulo ILIKE ?', ["%{$query}%"])
                  ->orWhereRaw('protocolo ILIKE ?', ["%{$query}%"]);
            })
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'title'    => $t->titulo,
                'subtitle' => $t->protocolo . ' · ' . ($t->status instanceof \BackedEnum ? $t->status->value : $t->status),
                'url'      => route('demandas.show', $t->id),
                'icon'     => 'checkbadge',
                'tag'      => 'DEMANDA',
            ])
            ->toArray();
    }

    private function normalize(string $query): string
    {
        // Strip leading special prefixes (#, @)
        $clean = ltrim(trim($query), '#@');

        // Remove caracteres nao-ASCII e de controle (ex: †, cursor artifacts, zero-width)
        $clean = preg_replace('/[^\x20-\x7E\xC0-\xFF]/u', '', $clean) ?? $clean;

        // Normaliza espacos multiplos
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    private function emptyResult(): array
    {
        return ['pae' => [], 'decretacoes' => [], 'rat' => [], 'demandas' => []];
    }
}
