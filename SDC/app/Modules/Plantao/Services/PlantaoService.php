<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlantaoService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plantao::query()->orderBy('data', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('plantonista_nome', 'like', "%{$search}%")
                    ->orWhere('data', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['periodo'])) {
            $query->where('periodo', $filters['periodo']);
        }

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Plantao
    {
        return Plantao::find($id);
    }

    public function create(array $data): Plantao
    {
        return Plantao::create($data);
    }

    public function update(int $id, array $data): Plantao
    {
        $plantao = Plantao::findOrFail($id);
        $plantao->update($data);
        return $plantao->fresh();
    }

    public function delete(int $id): bool
    {
        return Plantao::findOrFail($id)->delete();
    }

    /**
     * Autorizacao fina de edicao do turno (Decisao 2 do plano de
     * visualizar/editar): so o dono, e so enquanto ATIVO. Em PENDENTE_ACEITE
     * ou FINALIZADO* o snapshot ja foi declarado e possivelmente aceito pela
     * outra parte - editar reescreveria historico e esvaziaria o aceite
     * formal, entao nem o dono edita depois disso.
     *
     * Excecao: `plantao.passagem.encerrar_alheio` (supervisao/administracao)
     * cobre tambem a edicao alheia, pela mesma logica "dono, com excecao
     * supervisionada" ja usada no encerramento (spec 4.3). Toda edicao -
     * inclusive alheia - ja fica rastreada automaticamente pela trilha de
     * acoes do model (TrilhaDeAcoes/RegistroDeAcao avisa o dono "editado por
     * X"), sem precisar de coluna nova.
     */
    public function podeEditar(Plantao $plantao, User $user): bool
    {
        if ($plantao->status !== StatusPlantao::ATIVO) {
            return false;
        }

        if ((int) $plantao->plantonista_id === (int) $user->id) {
            return true;
        }

        return $user->can('plantao.passagem.encerrar_alheio');
    }

    public function getStatistics(array $filters = []): array
    {
        $porStatus = Plantao::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'total' => array_sum($porStatus),
            'ativos' => (int) ($porStatus[StatusPlantao::ATIVO->value] ?? 0),
            'pendentes_aceite' => (int) ($porStatus[StatusPlantao::PENDENTE_ACEITE->value] ?? 0),
            'finalizados_hoje' => Plantao::query()
                ->whereIn('status', [
                    StatusPlantao::FINALIZADO->value,
                    StatusPlantao::FINALIZADO_COM_DIVERGENCIA->value,
                ])
                ->whereDate('data', now()->toDateString())
                ->count(),
        ];
    }
}
