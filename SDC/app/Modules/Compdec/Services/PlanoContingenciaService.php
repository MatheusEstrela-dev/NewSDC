<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\DTOs\PlanoContingenciaDTO;
use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Support\LegacyParser;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PlanoContingenciaService
{
    public function listarPorOrgao(int $orgaoId, int $perPage = 20): LengthAwarePaginator
    {
        return CompdecPlanoContingencia::query()
            ->with(['aprovador:id,name', 'media'])
            ->doOrgao($orgaoId)
            ->orderByDesc('ativo')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $orgaoId, int $planoId): CompdecPlanoContingencia
    {
        return CompdecPlanoContingencia::query()
            ->with('aprovador:id,name')
            ->doOrgao($orgaoId)
            ->findOrFail($planoId);
    }

    public function criar(int $orgaoId, PlanoContingenciaDTO $dto, UploadedFile $arquivo): CompdecPlanoContingencia
    {
        return DB::transaction(function () use ($orgaoId, $dto, $arquivo): CompdecPlanoContingencia {
            $orgao = Orgao::findOrFail($orgaoId);

            $payload = array_merge($dto->toArray(), [
                'orgao_id' => $orgao->id,
                'tamanho_bytes' => $arquivo->getSize(),
                'ativo' => false, // sempre criado inativo; ativacao via metodo dedicado
            ]);

            $plano = CompdecPlanoContingencia::create($payload);
            $this->anexarArquivo($plano, $arquivo);

            // Se DTO solicitou ativo, ativa em seguida (passa pelo Observer pra desativar outros)
            if ($dto->ativo) {
                $this->ativar($orgaoId, $plano->id);
            }

            return $plano->fresh();
        });
    }

    public function atualizar(int $orgaoId, int $planoId, PlanoContingenciaDTO $dto, ?UploadedFile $arquivo = null): CompdecPlanoContingencia
    {
        return DB::transaction(function () use ($orgaoId, $planoId, $dto, $arquivo): CompdecPlanoContingencia {
            $plano = $this->obter($orgaoId, $planoId);

            $payload = $dto->toArray();
            unset($payload['ativo']); // ativo so muda via metodo ativar()

            if ($arquivo) {
                $payload['tamanho_bytes'] = $arquivo->getSize();
                $plano->clearMediaCollection(CompdecPlanoContingencia::MEDIA_ARQUIVO);
                $plano->update($payload);
                $this->anexarArquivo($plano, $arquivo);
            } else {
                $plano->update($payload);
            }

            return $plano->fresh();
        });
    }

    public function ativar(int $orgaoId, int $planoId): CompdecPlanoContingencia
    {
        return DB::transaction(function () use ($orgaoId, $planoId): CompdecPlanoContingencia {
            $plano = $this->obter($orgaoId, $planoId);

            // Desativa outros planos do mesmo orgao (evita violacao do partial unique)
            CompdecPlanoContingencia::query()
                ->doOrgao($orgaoId)
                ->where('id', '!=', $plano->id)
                ->where('ativo', true)
                ->update(['ativo' => false]);

            $plano->update(['ativo' => true]);

            return $plano->fresh();
        });
    }

    public function aprovar(int $orgaoId, int $planoId, int $aprovadorId): CompdecPlanoContingencia
    {
        $plano = $this->obter($orgaoId, $planoId);

        $plano->update([
            'aprovado_em' => now(),
            'aprovado_por' => $aprovadorId,
        ]);

        return $plano->fresh();
    }

    public function deletar(int $orgaoId, int $planoId): bool
    {
        $plano = $this->obter($orgaoId, $planoId);

        return (bool) $plano->delete();
    }

    public function download(int $orgaoId, int $planoId): StreamedResponse
    {
        $plano = $this->obter($orgaoId, $planoId);
        $media = $plano->getFirstMedia(CompdecPlanoContingencia::MEDIA_ARQUIVO);

        if (! $media) {
            abort(404, 'Arquivo nao encontrado para este plano de contingencia.');
        }

        return $media->toResponse(request());
    }

    private function anexarArquivo(CompdecPlanoContingencia $plano, UploadedFile $arquivo): Media
    {
        return $plano
            ->addMedia($arquivo)
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(CompdecPlanoContingencia::MEDIA_ARQUIVO, config('compdec.disk', 'compdec'));
    }

    /* ============================================================
     * ETL: com_plano_upload -> compdec_planos_contingencia
     * ============================================================ */

    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('planos');
        $report->dryRun = $dryRun;
        $connection = config('compdec.legacy_connection', 'legacy');

        DB::connection($connection)
            ->table('com_plano_upload')
            ->orderBy('id')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                foreach ($linhas as $row) {
                    $this->migrarPlanoLegado($row, $report, $dryRun);
                }
            });

        // Pos-ETL: marca o plano mais recente de cada orgao como ativo
        if (! $dryRun) {
            $this->marcarUltimoComoAtivo();
        }

        return $report;
    }

    private function migrarPlanoLegado(object $row, MigracaoReport $report, bool $dryRun): void
    {
        $legacyId = LegacyParser::toIntOrNull($row->id ?? null);
        $legacyOrgaoId = LegacyParser::toIntOrNull($row->id_compdec ?? $row->id_comdec ?? null);

        if ($legacyOrgaoId === null) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'sem id_compdec', $row, $dryRun);

            return;
        }

        $orgao = Orgao::query()->where('legacy_id', $legacyOrgaoId)->first();

        if (! $orgao) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'orgao_legado_inexistente', $row, $dryRun);

            return;
        }

        try {
            $payload = [
                'orgao_id' => $orgao->id,
                'versao' => LegacyParser::toStringOrNull($row->versao ?? null) ?? 'v1.0',
                'observacoes' => LegacyParser::toStringOrNull($row->observacoes ?? null),
                'ativo' => false, // marcado depois pelo passo marcarUltimoComoAtivo
                'legacy_arquivo' => LegacyParser::toStringOrNull($row->arquivo ?? null),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existente = CompdecPlanoContingencia::query()->where('legacy_id', $legacyId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = CompdecPlanoContingencia::query()->where('legacy_id', $legacyId)->first();
            $plano = CompdecPlanoContingencia::query()->updateOrCreate(['legacy_id' => $legacyId], $payload);

            $this->copiarArquivoLegado($plano, (string) ($row->arquivo ?? ''));

            $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();
            $this->logEtl($legacyId, $plano->id, $existente ? 'updated' : 'inserted', null, $row, false);
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    private function copiarArquivoLegado(CompdecPlanoContingencia $plano, string $arquivoLegado): void
    {
        if ($arquivoLegado === '') {
            return;
        }

        $base = (string) config('compdec.legacy_paths.planos', '');
        $path = rtrim($base, '/').'/'.ltrim($arquivoLegado, '/');

        if (! File::exists($path)) {
            $this->logEtl(
                $plano->legacy_id,
                $plano->id,
                'skipped',
                'arquivo_legado_inexistente: '.$path,
                null,
                false,
            );

            return;
        }

        if ($plano->getFirstMedia(CompdecPlanoContingencia::MEDIA_ARQUIVO)) {
            $plano->clearMediaCollection(CompdecPlanoContingencia::MEDIA_ARQUIVO);
        }

        $plano
            ->addMedia($path)
            ->preservingOriginal()
            ->usingFileName(basename($path))
            ->usingName('Plano '.$plano->versao)
            ->toMediaCollection(CompdecPlanoContingencia::MEDIA_ARQUIVO, config('compdec.disk', 'compdec'));

        $plano->update(['tamanho_bytes' => filesize($path) ?: null]);
    }

    /**
     * Pos-ETL: para cada orgao com planos migrados, marca o mais recente como ativo.
     */
    private function marcarUltimoComoAtivo(): void
    {
        $ultimosIds = CompdecPlanoContingencia::query()
            ->select(DB::raw('MAX(id) as id'))
            ->whereNotNull('legacy_id')
            ->groupBy('orgao_id')
            ->pluck('id');

        if ($ultimosIds->isEmpty()) {
            return;
        }

        // Desativa todos primeiro
        CompdecPlanoContingencia::query()
            ->whereIn('orgao_id', function ($q) use ($ultimosIds) {
                $q->select('orgao_id')->from('compdec_planos_contingencia')->whereIn('id', $ultimosIds);
            })
            ->update(['ativo' => false]);

        // Ativa apenas os ultimos
        CompdecPlanoContingencia::query()->whereIn('id', $ultimosIds)->update(['ativo' => true]);
    }

    private function logEtl(
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
            'recurso' => 'planos',
            'legacy_table' => 'com_plano_upload',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
