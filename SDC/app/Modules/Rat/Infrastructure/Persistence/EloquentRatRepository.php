<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Persistence;

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Services\RatFilterService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implementacao Eloquent do repositorio de RAT.
 *
 * Usa tabelas polimorficas (rat_ocorrencias + relatos) como fonte de dados.
 * Adapta para o formato esperado pelo frontend (compativel com estrutura Rat legada).
 */
class EloquentRatRepository implements RatRepositoryInterface
{
    public function __construct(
        private readonly RatFilterService $filterService,
    ) {}

    public function create(array $data): Rat
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        $ocorrencia = RatOcorrencia::create([
            'numero_bos'  => $this->generateProtocolo(),
            'status'      => 0,
            'created_by'  => $auth->id(),
            'updated_by'  => $auth->id(),
        ]);

        return $this->toRatModel($ocorrencia);
    }

    public function findById(string $id): ?Rat
    {
        $ocorrencia = RatOcorrencia::with(['relatosMorph'])->find($id);

        if (!$ocorrencia) {
            return null;
        }

        return $this->toRatModel($ocorrencia);
    }

    public function paginate(RatFilterDTO $filters): LengthAwarePaginator
    {
        $query = RatOcorrencia::with(['relatosMorph'])
            ->orderBy('created_at', 'desc');

        if ($filters->status !== null && $filters->status !== '') {
            $statusInt = match($filters->status) {
                'rascunho', '0' => 0,
                'finalizado', '1' => 1,
                default => (int) $filters->status,
            };
            $query->where('status', $statusInt);
        }

        if ($filters->protocolo) {
            $query->where('numero_bos', 'like', "%{$filters->protocolo}%");
        }

        if ($filters->ano) {
            $query->whereYear('created_at', $filters->ano);
        }

        $paginator = $query->paginate($filters->perPage);

        $paginator->getCollection()->transform(fn($oc) => $this->toRatModel($oc));

        return $paginator;
    }

    public function getMunicipalities(): array
    {
        return RatRelatoDadosGerais::whereNotNull('local_municipio')
            ->distinct()
            ->pluck('local_municipio')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public function delete(string $id): void
    {
        RatOcorrencia::where('id', $id)->delete();
    }

    public function updateStatus(string $id, string $status): void
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        RatOcorrencia::where('id', $id)->update([
            'status'     => $status === 'finalizado' ? 1 : 0,
            'updated_by' => $auth->id(),
        ]);
    }

    public function update(string $id, array $data): Rat
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->update(['updated_by' => $auth->id()]);

        return $this->toRatModel($ocorrencia->fresh(['relatosMorph']));
    }

    public function getLatestSequence(int $year): int
    {
        $latest = RatOcorrencia::where('numero_bos', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if (!$latest) {
            return 0;
        }

        return (int) substr($latest, strrpos($latest, '-') + 1);
    }

    private function generateProtocolo(): string
    {
        $year = now()->year;
        $seq  = $this->getLatestSequence($year) + 1;

        return sprintf('RAT-%d-%06d', $year, $seq);
    }

    private function toRatModel(RatOcorrencia $ocorrencia): Rat
    {
        $dadosGerais = null;
        $recursos    = [];
        $envolvidos  = [];
        $vistoria    = null;

        foreach ($ocorrencia->relatosMorph ?? [] as $relato) {
            $conteudo = $relato->conteudo;
            if (!$conteudo) continue;

            $type = class_basename($conteudo);
            match ($type) {
                'RatRelatoDadosGerais' => $dadosGerais = $conteudo->toArray(),
                'RatRelatoRecurso'     => $recursos[] = $conteudo->toArray(),
                'RatRelatoEnvolvidos'  => $envolvidos[] = $conteudo->toArray(),
                'RatRelatoVistoria'    => $vistoria = $conteudo->toArray(),
                default                => null,
            };
        }

        $rat = new Rat();
        $rat->id          = $ocorrencia->id;
        $rat->protocolo   = $ocorrencia->numero_bos;
        $rat->status      = $ocorrencia->status === 1 ? 'finalizado' : 'rascunho';
        $rat->tem_vistoria = $vistoria !== null;
        $rat->dados_gerais = $dadosGerais ?? [];
        $rat->local        = [
            'municipio' => $dadosGerais['local_municipio'] ?? null,
            'uf'        => $dadosGerais['local_estadouf'] ?? $dadosGerais['local_uf'] ?? 'MG',
        ];
        $rat->endereco     = [
            'logradouro' => $dadosGerais['local_logradoura_1'] ?? $dadosGerais['local_logradouro'] ?? null,
            'numero'     => $dadosGerais['local_numero'] ?? null,
            'bairro'     => $dadosGerais['local_bairro'] ?? null,
            'cep'        => $dadosGerais['local_cep'] ?? null,
        ];
        $rat->comunicacao  = [
            'data'        => $dadosGerais['com_ocorrencia_data'] ?? null,
            'atendimento' => $dadosGerais['com_ocorrencia_atendimento'] ?? null,
        ];
        $rat->recursos     = $recursos;
        $rat->envolvidos   = $envolvidos;
        $rat->vistoria     = $vistoria ?? [];
        $rat->historico    = $ocorrencia->historicos ? $ocorrencia->historicos->toArray() : [];
        $rat->anexos       = [];
        $rat->created_at   = $ocorrencia->created_at;
        $rat->updated_at   = $ocorrencia->updated_at;

        return $rat;
    }
}
