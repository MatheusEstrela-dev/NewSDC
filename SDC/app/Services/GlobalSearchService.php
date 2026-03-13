<?php

namespace App\Services;

use App\Modules\Rat\Models\Rat;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;
use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
        protected ProcessoRepositoryInterface $processoRepository,
        protected OrgaoRepositoryInterface $orgaoRepository,
        protected TreinamentoRepositoryInterface $treinamentoRepository
    ) {}

    public function search(string $query, int $limitPerCategory = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $results = [
            'actions' => $this->searchActions($query),
            'rats' => $this->searchRats($query, $limitPerCategory),
            'demandas' => $this->searchDemandas($query, $limitPerCategory),
            'orgaos' => $this->searchOrgaos($query, $limitPerCategory),
            'processos' => $this->searchProcessos($query, $limitPerCategory),
            'treinamentos' => $this->searchTreinamentos($query, $limitPerCategory),
        ];

        // Filter out empty categories
        return array_filter($results, fn($category) => !empty($category) && count($category) > 0);
    }

    protected function searchActions(string $query): array
    {
        $actions = [
            ['id' => 'act_1', 'title' => 'Novo RAT', 'subtitle' => 'Criar novo relatório', 'url' => route('rat.create'), 'icon' => 'document', 'tag' => 'Criar'],
            ['id' => 'act_2', 'title' => 'Nova Demanda', 'subtitle' => 'Abrir chamado técnico', 'url' => route('demandas.create'), 'icon' => 'checkbadge', 'tag' => 'Criar'],
            ['id' => 'act_3', 'title' => 'Meu Perfil', 'subtitle' => 'Gerenciar conta', 'url' => route('profile.edit'), 'icon' => 'user', 'tag' => 'Config'],
            ['id' => 'act_4', 'title' => 'Dashboard', 'subtitle' => 'Ir para página inicial', 'url' => route('dashboard'), 'icon' => 'home', 'tag' => 'Nav'],
            ['id' => 'act_5', 'title' => 'Log Viewer', 'subtitle' => 'Logs do sistema', 'url' => route('log-viewer.index'), 'icon' => 'bolt', 'tag' => 'Admin'],
            ['id' => 'act_6', 'title' => 'Sair', 'subtitle' => 'Fazer logout', 'url' => route('logout'), 'icon' => 'logout', 'tag' => 'Auth'],
        ];

        return collect($actions)
            ->filter(fn($action) => str_contains(strtolower($action['title']), strtolower($query)) || str_contains(strtolower($action['subtitle']), strtolower($query)))
            ->values()
            ->toArray();
    }

    protected function searchRats(string $query, int $limit): array
    {
        try {
            $items = Rat::where('protocolo', 'like', "%{$query}%")
                ->limit($limit)
                ->get();

            return $items->map(function ($item) {
                return [
                    'id'       => $item->id,
                    'title'    => $item->protocolo,
                    'subtitle' => 'RAT - ' . ($item->status ?? 'N/A'),
                    'url'      => route('rat.show', $item->id),
                    'type'     => 'rat',
                    'icon'     => 'document',
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function searchDemandas(string $query, int $limit): array
    {
        try {
            $paginator = $this->taskRepository->findAll(['search' => $query], $limit);
            return collect($paginator->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->titulo ?? $item->protocolo,
                    'subtitle' => 'Demanda',
                    'url' => route('demandas.show', $item->id),
                    'type' => 'demanda',
                    'icon' => 'checkbadge'
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function searchOrgaos(string $query, int $limit): array
    {
        try {
            $paginator = $this->orgaoRepository->findAll(['search' => $query], $limit);
            return collect($paginator->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->nome,
                    'subtitle' => $item->municipio->nome ?? 'Órgão',
                    // Assuming compdec.show uses ID, or route name might be different.
                    // If compdec.show fails, we might need to check compdec.php.
                    // But usually module routes are straightforward.
                    'url' => route('compdec.show', $item->id), 
                    'type' => 'orgao',
                    'icon' => 'building'
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function searchProcessos(string $query, int $limit): array
    {
        try {
            $paginator = $this->processoRepository->findAll(['search' => $query], $limit);
            return collect($paginator->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => 'FIDE: ' . ($item->n_protocolo_fide ?? 'N/A'),
                    'subtitle' => 'Processo',
                    'url' => route('decretacoes.show', $item->id),
                    'type' => 'processo',
                    'icon' => 'folder'
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function searchTreinamentos(string $query, int $limit): array
    {
        try {
            $paginator = $this->treinamentoRepository->findAll(['search' => $query], $limit);
            return collect($paginator->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->titulo,
                    'subtitle' => 'Treinamento - ' . ($item->status ?? 'N/A'),
                    'url' => route('treinamentos.show', $item->id),
                    'type' => 'treinamento',
                    'icon' => 'academic-cap'
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}