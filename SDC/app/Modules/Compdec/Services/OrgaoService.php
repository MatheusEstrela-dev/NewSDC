<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Models\User;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Support\LegacyParser;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class OrgaoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarOrgaos(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Orgao::query()
            ->with(['orgaoSuperior:id,codigo,nome,tipo', 'municipio:id,nome,uf'])
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

    /* ============================================================
     * ETL: Migracao do legado (com_comdec -> compdec_orgaos)
     * ============================================================ */

    /**
     * Migra orgaos do banco legado (com_comdec) para compdec_orgaos.
     *
     * Idempotente: usa updateOrCreate por legacy_id.
     * Em dry-run, nao persiste, apenas conta o que seria feito.
     */
    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('orgaos');
        $report->dryRun = $dryRun;
        $connection = config('compdec.legacy_connection', 'legacy');

        DB::connection($connection)
            ->table('com_comdec')
            ->orderBy('id_comdec')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                foreach ($linhas as $row) {
                    $this->migrarOrgaoLegado($row, $report, $dryRun);
                }
            });

        return $report;
    }

    private function migrarOrgaoLegado(object $row, MigracaoReport $report, bool $dryRun): void
    {
        $legacyId = LegacyParser::toIntOrNull($row->id_comdec ?? null);

        if ($legacyId === null) {
            $report->registrarSkip();
            $this->logEtl('orgaos', 'com_comdec', null, null, 'skipped', 'sem id_comdec', $row, $dryRun);

            return;
        }

        try {
            $payload = $this->mapearLegadoParaOrgao($row, $legacyId);

            if ($dryRun) {
                $existente = Orgao::query()->where('legacy_id', $legacyId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = Orgao::query()->where('legacy_id', $legacyId)->first();
            $orgao = Orgao::query()->updateOrCreate(['legacy_id' => $legacyId], $payload);

            if ($existente) {
                $report->registrarAtualizacao();
                $this->logEtl('orgaos', 'com_comdec', $legacyId, $orgao->id, 'updated', null, $row, false);
            } else {
                $report->registrarInsercao();
                $this->logEtl('orgaos', 'com_comdec', $legacyId, $orgao->id, 'inserted', null, $row, false);
            }
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl('orgaos', 'com_comdec', $legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearLegadoParaOrgao(object $row, int $legacyId): array
    {
        return [
            'tipo' => TipoOrgao::COMPDEC->value,
            'status' => 'ativo',
            'codigo' => LegacyParser::toStringOrNull($row->codigo ?? null) ?? "LEG-{$legacyId}",
            'nome' => LegacyParser::toStringOrNull($row->nome ?? null) ?? "(sem nome) {$legacyId}",
            'municipio_id' => LegacyParser::toIntOrNull($row->municipio_id ?? $row->mun_id ?? null),
            'email' => LegacyParser::toStringOrNull($row->email ?? null),
            'email_secundario' => LegacyParser::toStringOrNull($row->email2 ?? null),
            'email_terciario' => LegacyParser::toStringOrNull($row->email3 ?? null),
            'telefone' => LegacyParser::toStringOrNull($row->fone_com ?? $row->telefone ?? null),
            'telefone_secundario' => LegacyParser::toStringOrNull($row->fone_com2 ?? null),
            'fax' => LegacyParser::toStringOrNull($row->fax ?? null),
            'endereco' => LegacyParser::toStringOrNull($row->endereco ?? null),
            'lei_criacao_numero' => LegacyParser::toStringOrNull($row->lei_num ?? null),
            'lei_criacao_data' => LegacyParser::toDate($row->lei_data ?? null)?->toDateString(),
            'decreto_numero' => LegacyParser::toStringOrNull($row->dec_num ?? null),
            'decreto_data' => LegacyParser::toDate($row->dec_data ?? null)?->toDateString(),
            'portaria_numero' => LegacyParser::toStringOrNull($row->port_numero ?? null),
            'portaria_data' => LegacyParser::toDate($row->port_data ?? null)?->toDateString(),
            'qtd_efetivo' => LegacyParser::toIntOrNull($row->efetivo_qtd ?? null) ?? 0,
            'qtd_nupdec' => LegacyParser::toIntOrNull($row->nupdec_qtd ?? null) ?? 0,
            'tem_sede_propria' => LegacyParser::toBool($row->sede_propria ?? null),
            'tem_viatura' => LegacyParser::toBool($row->viatura ?? null),
            'tem_mapeamento_risco' => LegacyParser::toBool($row->mapeamento_risco ?? null),
            'tem_simulado' => LegacyParser::toBool($row->simulado ?? null),
            'tem_cartao_pdc' => LegacyParser::toBool($row->cartao_pdc ?? null),
            'metadata' => [
                'capacidades' => [
                    'tem_computador' => LegacyParser::toBool($row->computador ?? null),
                    'tem_curso_gestao' => LegacyParser::toBool($row->curso_gestao ?? null),
                    'data_curso_gestao' => LegacyParser::toDate($row->curso_gestao_data ?? null)?->toDateString(),
                    'tem_curso_sco' => LegacyParser::toBool($row->curso_sco ?? null),
                    'data_curso_sco' => LegacyParser::toDate($row->curso_sco_data ?? null)?->toDateString(),
                    'tem_workshop_pdc' => LegacyParser::toBool($row->workshop_pdc ?? null),
                    'data_workshop_pdc' => LegacyParser::toDate($row->workshop_pdc_data ?? null)?->toDateString(),
                    'experiencia_dc' => LegacyParser::toStringOrNull($row->experiencia_dc ?? null),
                    'capacitacao_nupdec' => LegacyParser::toStringOrNull($row->capacitacao_nupdec ?? null),
                    'obs_capacidades' => LegacyParser::toStringOrNull($row->obs ?? null),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|object|null  $payload
     */
    private function logEtl(
        string $recurso,
        string $legacyTable,
        ?int $legacyId,
        ?int $newId,
        string $acao,
        ?string $motivo,
        mixed $payload,
        bool $dryRun,
    ): void {
        if ($dryRun) {
            return;
        }

        DB::table('compdec_etl_log')->insert([
            'recurso' => $recurso,
            'legacy_table' => $legacyTable,
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
