<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Jobs\EmitirCertificadosTreinamentoJob;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class TreinamentoService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Treinamento::query()->with(['modulos']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                  ->orWhere('descricao', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Treinamento
    {
        // Nota: `frequencias` pertence a Modulo, nao a Treinamento diretamente
        // (bug pre-existente aqui tentava eager-load de uma relacao inexistente).
        return Treinamento::with(['modulos.frequencias', 'inscricoes.inscrito', 'inscricoes.certificado'])->find($id);
    }

    public function create(array $data): Treinamento
    {
        return Treinamento::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $treinamento = Treinamento::find($id);
        if (!$treinamento) {
            return false;
        }
        return $treinamento->update($data);
    }

    public function delete(int $id): bool
    {
        $treinamento = Treinamento::find($id);
        if (!$treinamento) {
            return false;
        }
        return $treinamento->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Treinamento::count(),
            'planejados' => Treinamento::where('status', StatusTreinamento::PLANEJADO->value)->count(),
            'em_andamento' => Treinamento::where('status', StatusTreinamento::EM_ANDAMENTO->value)->count(),
            'concluidos' => Treinamento::where('status', StatusTreinamento::CONCLUIDO->value)->count(),
        ];
    }

    public function export(array $filters = []): array
    {
        $query = Treinamento::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->toArray();
    }

    /**
     * Publica o treinamento no catalogo do Portal do Cidadao (gera o slug
     * publico). Nao depende do status interno (PLANEJADO/EM_ANDAMENTO ainda
     * podem ser publicados para abrir inscricao antecipada).
     */
    public function publicar(Treinamento $treinamento): void
    {
        if ($treinamento->status === StatusTreinamento::CANCELADO || $treinamento->status === StatusTreinamento::CONCLUIDO) {
            throw new \DomainException('Nao e possivel publicar um treinamento cancelado ou concluido.');
        }

        $treinamento->publicarNoPortal();
    }

    public function liberarPresenca(Treinamento $treinamento, User $por): void
    {
        if (!$treinamento->status->podeRegistrarFrequencia()) {
            throw new \DomainException('So e possivel liberar a presenca de um treinamento Em Andamento.');
        }

        $treinamento->liberarPresenca($por);
    }

    public function bloquearPresenca(Treinamento $treinamento): void
    {
        $treinamento->bloquearPresenca();
    }

    public function transicionarStatus(Treinamento $treinamento, StatusTreinamento $novoStatus): void
    {
        if (!$treinamento->status->canTransitionTo($novoStatus)) {
            throw new \DomainException(
                "Nao e possivel mudar o status de {$treinamento->status->getLabel()} para {$novoStatus->getLabel()}."
            );
        }

        if ($novoStatus === StatusTreinamento::CONCLUIDO) {
            $treinamento->finalizar();
            EmitirCertificadosTreinamentoJob::dispatch($treinamento->id);
            return;
        }

        $treinamento->update(['status' => $novoStatus->value]);
    }
}
