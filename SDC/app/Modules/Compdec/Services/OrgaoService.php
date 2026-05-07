<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Models\User;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OrgaoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarOrgaos(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Orgao::query()
            ->with(['orgaoSuperior:id,codigo,nome,tipo'])
            ->withCount(['usuarios', 'orgaosSubordinados as subordinados_count'])
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->when($filtros['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscarPorTermo($termo))
            ->orderBy('tipo')
            ->orderBy('nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int|array<string, int>>
     */
    public function obterEstatisticas(): array
    {
        $row = Orgao::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = ?) AS ativos,
                COUNT(*) FILTER (WHERE tipo = ?) AS compdec_count,
                COUNT(*) FILTER (WHERE tipo = ?) AS redec_count,
                COUNT(*) FILTER (WHERE tipo = ?) AS cedec_count
            ', [
                'ativo',
                TipoOrgao::COMPDEC->value,
                TipoOrgao::REDEC->value,
                TipoOrgao::CEDEC->value,
            ])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'ativos' => (int) ($row->ativos ?? 0),
            'por_tipo' => [
                'compdec' => (int) ($row->compdec_count ?? 0),
                'redec' => (int) ($row->redec_count ?? 0),
                'cedec' => (int) ($row->cedec_count ?? 0),
            ],
        ];
    }

    public function obterOrgao(int $id): Orgao
    {
        return Orgao::query()
            ->with(['orgaoSuperior:id,codigo,nome,tipo', 'prefeitura'])
            ->withCount(['usuarios'])
            ->findOrFail($id);
    }

    public function criarOrgao(OrgaoDTO $dto): Orgao
    {
        return DB::transaction(function () use ($dto): Orgao {
            $this->validarHierarquia($dto);

            $data = $dto->toArray();

            // codigo auto-gerado quando vazio
            if (empty($data['codigo'])) {
                $data['codigo'] = $this->gerarCodigoAutomatico($dto->tipo);
            }

            return Orgao::create($data);
        });
    }

    public function atualizarOrgao(int $id, OrgaoDTO $dto): Orgao
    {
        return DB::transaction(function () use ($id, $dto): Orgao {
            $orgao = Orgao::findOrFail($id);
            $this->validarHierarquia($dto, $id);

            $data = $dto->toArray();

            // se codigo nao mudou, manter
            if (empty($data['codigo'])) {
                $data['codigo'] = $orgao->codigo;
            }

            // metadata: merge inteligente para nao perder chaves nao editadas pelo form
            if (isset($data['metadata']) && is_array($data['metadata'])) {
                $existing = $orgao->metadata ?? [];
                $data['metadata'] = array_replace_recursive($existing, $data['metadata']);
            }

            $orgao->update($data);

            return $orgao->fresh(['orgaoSuperior', 'prefeitura']);
        });
    }

    public function deletarOrgao(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $orgao = Orgao::findOrFail($id);

            if ($orgao->orgaosSubordinados()->exists()) {
                throw new InvalidArgumentException('Nao e possivel deletar um orgao que possui subordinados.');
            }

            return (bool) $orgao->delete();
        });
    }

    public function restaurarOrgao(int $id): bool
    {
        $orgao = Orgao::onlyTrashed()->findOrFail($id);

        return (bool) $orgao->restore();
    }

    /**
     * @return Collection<int, Orgao>
     */
    public function obterCedecs(): Collection
    {
        return Orgao::query()
            ->cedec()
            ->ativo()
            ->select(['id', 'codigo', 'nome', 'tipo'])
            ->orderBy('nome')
            ->get();
    }

    /**
     * @return Collection<int, Orgao>
     */
    public function obterRedecs(?int $cedecId = null): Collection
    {
        return Orgao::query()
            ->redec()
            ->ativo()
            ->when($cedecId, fn ($q, $id) => $q->where('orgao_superior_id', $id))
            ->select(['id', 'codigo', 'nome', 'tipo', 'orgao_superior_id'])
            ->orderBy('nome')
            ->get();
    }

    /**
     * @return Collection<int, Orgao>
     */
    public function obterCompdecs(?int $redecId = null): Collection
    {
        return Orgao::query()
            ->compdec()
            ->ativo()
            ->when($redecId, fn ($q, $id) => $q->where('orgao_superior_id', $id))
            ->select(['id', 'codigo', 'nome', 'tipo', 'municipio_id', 'orgao_superior_id'])
            ->orderBy('nome')
            ->get();
    }

    /**
     * Arvore CEDEC -> REDEC -> COMPDEC para selects encadeados ou navegacao.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obterArvoreHierarquica(): array
    {
        $orgaos = Orgao::query()
            ->ativo()
            ->select(['id', 'codigo', 'nome', 'tipo', 'orgao_superior_id', 'municipio_id'])
            ->orderBy('tipo')
            ->orderBy('nome')
            ->get();

        $cedecs = $orgaos->where('tipo', TipoOrgao::CEDEC)->values();
        $redecs = $orgaos->where('tipo', TipoOrgao::REDEC)->groupBy('orgao_superior_id');
        $compdecs = $orgaos->where('tipo', TipoOrgao::COMPDEC)->groupBy('orgao_superior_id');

        return $cedecs->map(fn (Orgao $cedec): array => [
            'id' => $cedec->id,
            'codigo' => $cedec->codigo,
            'nome' => $cedec->nome,
            'redecs' => ($redecs[$cedec->id] ?? collect())->map(fn (Orgao $redec): array => [
                'id' => $redec->id,
                'codigo' => $redec->codigo,
                'nome' => $redec->nome,
                'compdecs' => ($compdecs[$redec->id] ?? collect())->map(fn (Orgao $c): array => [
                    'id' => $c->id,
                    'codigo' => $c->codigo,
                    'nome' => $c->nome,
                    'municipio_id' => $c->municipio_id,
                ])->values()->all(),
            ])->values()->all(),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $pivotAttrs
     */
    public function vincularUsuarioAOrgao(int $orgaoId, int $userId, array $pivotAttrs = []): bool
    {
        $orgao = Orgao::findOrFail($orgaoId);
        User::query()->findOrFail($userId);

        $orgao->usuarios()->syncWithoutDetaching([
            $userId => array_merge([
                'funcao' => 'agente',
                'is_principal' => false,
            ], $pivotAttrs),
        ]);

        return true;
    }

    public function desvincularUsuario(int $orgaoId, int $userId): bool
    {
        $orgao = Orgao::findOrFail($orgaoId);

        return (bool) $orgao->usuarios()->detach($userId);
    }

    public function uploadFotoCoordenador(int $orgaoId, UploadedFile $arquivo): Media
    {
        $orgao = Orgao::findOrFail($orgaoId);

        return $orgao
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Orgao::MEDIA_FOTO_COORDENADOR, config('compdec.disk', 'compdec'));
    }

    public function removerFotoCoordenador(int $orgaoId): bool
    {
        $orgao = Orgao::findOrFail($orgaoId);
        $orgao->clearMediaCollection(Orgao::MEDIA_FOTO_COORDENADOR);

        return true;
    }

    private function validarHierarquia(OrgaoDTO $dto, ?int $excludeId = null): void
    {
        if ($excludeId !== null && $dto->orgaoSuperiorId === $excludeId) {
            throw new InvalidArgumentException('Um orgao nao pode ser superior de si mesmo.');
        }

        if ($dto->tipo === TipoOrgao::CEDEC->value && $dto->orgaoSuperiorId !== null) {
            throw new InvalidArgumentException('CEDEC nao deve ter orgao superior.');
        }

        if ($dto->orgaoSuperiorId === null) {
            return;
        }

        $superior = Orgao::find($dto->orgaoSuperiorId);
        if (! $superior) {
            throw new ModelNotFoundException('Orgao superior nao encontrado.');
        }

        if ($dto->tipo === TipoOrgao::REDEC->value && $superior->tipo !== TipoOrgao::CEDEC) {
            throw new InvalidArgumentException('REDEC deve ter um CEDEC como orgao superior.');
        }
        if ($dto->tipo === TipoOrgao::COMPDEC->value && $superior->tipo !== TipoOrgao::REDEC) {
            throw new InvalidArgumentException('COMPDEC deve ter um REDEC como orgao superior.');
        }
    }

    private function gerarCodigoAutomatico(string $tipo): string
    {
        $prefix = match ($tipo) {
            TipoOrgao::CEDEC->value => 'CEDEC',
            TipoOrgao::REDEC->value => 'REDEC',
            default => 'COMPDEC',
        };

        // proximo numero sequencial dentro do tipo
        $proximo = (int) Orgao::query()
            ->where('codigo', 'ilike', "{$prefix}-%")
            ->count() + 1;

        return sprintf('%s-%06d', $prefix, $proximo);
    }
}
