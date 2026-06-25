<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class PmdaPlanoService extends BaseService
{
    /**
     * Status que consideram o municipio com PMDA "pendente" (em aberto),
     * impedindo abrir outro. Espelha o legado (gestaocedec verificaCriarPmda:
     * status IN 0,2) ampliado com COMPLETO, que e parte do ciclo de edicao.
     *
     * @return list<string>
     */
    public static function statusPendente(): array
    {
        return [
            PmdaStatus::RASCUNHO->value,
            PmdaStatus::COMPLETO->value,
            PmdaStatus::EM_ANALISE->value,
        ];
    }

    public function criar(int $municipioId, int $userId, array $data): PmdaPlano
    {
        $pendente = PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->whereIn('status', self::statusPendente())
            ->first();

        if ($pendente !== null) {
            throw new \DomainException(
                'Este município já possui um PMDA em aberto ('.$pendente->status->getLabel().
                ', protocolo '.($pendente->protocolo ?? '—').'). Conclua, cancele ou edite o existente antes de criar outro.'
            );
        }

        return PmdaPlano::create(array_merge($data, [
            'municipio_id' => $municipioId,
            'status'       => PmdaStatus::RASCUNHO,
            'created_by'   => $userId,
        ]));
    }

    public function atualizar(PmdaPlano $plano, array $data, int $userId): PmdaPlano
    {
        $plano->update(array_merge($data, [
            'updated_by'          => $userId,
            'dt_ultima_alteracao' => now(),
        ]));

        return $plano->refresh();
    }

    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PmdaPlano::query()->with('municipio')->latest('data');
        $query = $this->applyFilters($query, $filtros, ['municipio_id', 'status']);

        if (! empty($filtros['buscar'])) {
            $termo = $filtros['buscar'];
            $query->where(function ($q) use ($termo) {
                $q->where('protocolo', 'ilike', "%{$termo}%")
                    ->orWhereHas('municipio', fn ($m) => $m->where('nome', 'ilike', "%{$termo}%"));
            });
        }
        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('data', '>=', $filtros['data_inicio']);
        }
        if (! empty($filtros['data_fim'])) {
            $query->whereDate('data', '<=', $filtros['data_fim']);
        }

        return $this->paginate($query, $perPage);
    }

    /** Linhas para exportacao CSV (respeita filtros). */
    public function exportar(array $filtros = []): array
    {
        $query = PmdaPlano::query()->with('municipio')->latest('data');
        $query = $this->applyFilters($query, $filtros, ['municipio_id', 'status']);

        return $query->get()->map(fn (PmdaPlano $p) => [
            'Protocolo' => $p->protocolo,
            'Municipio' => $p->municipio?->nome,
            'Situacao'  => $p->status->getLabel(),
            'Criacao'   => $p->data?->format('d/m/Y'),
        ])->all();
    }

    /**
     * Recalcula RASCUNHO <-> COMPLETO conforme comunidades e representantes.
     * Nao mexe em planos ja submetidos (EM_ANALISE/APROVADO/ATENDIDO e terminais).
     */
    public const REPRESENTANTES_POR_COMUNIDADE = 3;

    public function recalcularStatus(PmdaPlano $plano): PmdaPlano
    {
        $intocaveis = [
            PmdaStatus::EM_ANALISE, PmdaStatus::APROVADO, PmdaStatus::ATENDIDO,
            PmdaStatus::ARQUIVADO, PmdaStatus::ANULADO, PmdaStatus::CANCELADO, PmdaStatus::ENCERRADO,
        ];
        if (in_array($plano->status, $intocaveis, true)) {
            return $plano;
        }

        $totComunidades = $plano->comunidades()->count();
        $todasComRepresentantes = $totComunidades > 0
            && $plano->comunidades()
                ->withCount('representantes')
                ->get()
                ->every(fn ($c) => $c->representantes_count >= self::REPRESENTANTES_POR_COMUNIDADE);

        $novo = $todasComRepresentantes ? PmdaStatus::COMPLETO : PmdaStatus::RASCUNHO;
        if ($plano->status !== $novo) {
            $plano->update(['status' => $novo]);
        }

        return $plano->refresh();
    }
}
