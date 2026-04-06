<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Rat\Models\Rat;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Support\Facades\Cache;

class GlobalSearchService
{
    private const LIMIT = 5;
    private const CACHE_TTL = 60;

    public function search(string $query): array
    {
        $normalized = strtolower(trim($query));
        $key = 'global_search:' . md5($normalized);

        return Cache::store('redis')
            ->tags(['global_search'])
            ->remember($key, self::CACHE_TTL, fn () => $this->runSearch($query));
    }

    private function runSearch(string $query): array
    {
        return [
            'decretacoes' => $this->searchDecretacoes($query),
            'rat'         => $this->searchRat($query),
            'demandas'    => $this->searchDemandas($query),
            'pae'         => $this->searchPae($query),
        ];
    }

    private function searchDecretacoes(string $query): array
    {
        return Processo::without(['municipios', 'desastres'])
            ->select(['id', 'n_protocolo_fide', 'tipo_desastre'])
            ->where('n_protocolo_fide', 'like', $query . '%')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->n_protocolo_fide,
                'subtitle' => $p->tipo_desastre ?? 'Decretacao',
                'url'      => route('decretacoes.show', $p->id),
                'icon'     => 'scale',
                'tag'      => 'DECRETO',
            ])
            ->toArray();
    }

    private function searchRat(string $query): array
    {
        return Rat::select(['id', 'protocolo', 'status'])
            ->where('protocolo', 'like', $query . '%')
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
            ->where('titulo', 'like', '%' . $query . '%')
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

    private function searchPae(string $query): array
    {
        return PaeProtocolo::select(['id', 'num_protocolo', 'sei_numero', 'status'])
            ->where('num_protocolo', 'like', $query . '%')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($p) => [
                'id'       => $p->id,
                'title'    => $p->num_protocolo,
                'subtitle' => $p->sei_numero ? 'SEI: ' . $p->sei_numero : 'PAE',
                'url'      => route('pae.protocolos.index'),
                'icon'     => 'document',
                'tag'      => 'PAE',
            ])
            ->toArray();
    }
}
