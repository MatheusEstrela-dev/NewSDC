<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\DTOs\AnexoDTO;
use App\Modules\Compdec\Models\CompdecAnexo;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Support\LegacyParser;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnexoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarPorOrgao(int $orgaoId, int $perPage = 20, array $filtros = []): LengthAwarePaginator
    {
        return CompdecAnexo::query()
            ->with('media')
            ->doOrgao($orgaoId)
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->doTipo($tipo))
            ->orderByDesc('data_emissao')
            ->orderBy('titulo')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $orgaoId, int $anexoId): CompdecAnexo
    {
        return CompdecAnexo::query()
            ->doOrgao($orgaoId)
            ->findOrFail($anexoId);
    }

    public function criar(int $orgaoId, AnexoDTO $dto, ?UploadedFile $arquivo = null): CompdecAnexo
    {
        return DB::transaction(function () use ($orgaoId, $dto, $arquivo): CompdecAnexo {
            $orgao = Orgao::findOrFail($orgaoId);

            $payload = array_merge($dto->toArray(), ['orgao_id' => $orgao->id]);
            $anexo = CompdecAnexo::create($payload);

            if ($arquivo) {
                $this->anexarArquivo($anexo, $arquivo);
            }

            return $anexo->fresh();
        });
    }

    public function atualizar(int $orgaoId, int $anexoId, AnexoDTO $dto, ?UploadedFile $arquivo = null): CompdecAnexo
    {
        return DB::transaction(function () use ($orgaoId, $anexoId, $dto, $arquivo): CompdecAnexo {
            $anexo = $this->obter($orgaoId, $anexoId);
            $anexo->update($dto->toArray());

            if ($arquivo) {
                $anexo->clearMediaCollection(CompdecAnexo::MEDIA_ARQUIVO);
                $this->anexarArquivo($anexo, $arquivo);
            }

            return $anexo->fresh();
        });
    }

    public function deletar(int $orgaoId, int $anexoId): bool
    {
        $anexo = $this->obter($orgaoId, $anexoId);

        return (bool) $anexo->delete();
    }

    public function download(int $orgaoId, int $anexoId): StreamedResponse
    {
        $anexo = $this->obter($orgaoId, $anexoId);
        $media = $anexo->getFirstMedia(CompdecAnexo::MEDIA_ARQUIVO);

        if (! $media) {
            abort(404, 'Arquivo nao encontrado para este anexo.');
        }

        return $media->toResponse(request());
    }

    /**
     * @return Collection<int, CompdecAnexo>
     */
    public function listarProximosDoVencimento(?int $diasJanela = null): Collection
    {
        $dias = $diasJanela ?? (int) config('compdec.anexo_alerta_vencimento_dias', 30);

        return CompdecAnexo::query()
            ->with('orgao:id,codigo,nome,tipo')
            ->proximosDoVencimento($dias)
            ->orderBy('data_validade')
            ->get();
    }

    private function anexarArquivo(CompdecAnexo $anexo, UploadedFile $arquivo): Media
    {
        $disk = (string) config('compdec.disk', 'compdec');

        return $anexo
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(CompdecAnexo::MEDIA_ARQUIVO, $disk);
    }

    /* ============================================================
     * ETL: Migracao de com_anexo -> compdec_anexos
     * ============================================================ */

    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('anexos');
        $report->dryRun = $dryRun;
        $connection = config('compdec.legacy_connection', 'legacy');

        DB::connection($connection)
            ->table('com_anexo')
            ->orderBy('id')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                foreach ($linhas as $row) {
                    $this->migrarAnexoLegado($row, $report, $dryRun);
                }
            });

        return $report;
    }

    private function migrarAnexoLegado(object $row, MigracaoReport $report, bool $dryRun): void
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
                'tipo' => $this->mapearTipoLegado($row),
                'titulo' => LegacyParser::toStringOrNull($row->titulo ?? $row->descricao ?? null) ?? '(sem titulo)',
                'descricao' => LegacyParser::toStringOrNull($row->descricao ?? null),
                'numero' => LegacyParser::toStringOrNull($row->numero ?? null),
                'data_emissao' => LegacyParser::toDate($row->data_emissao ?? null)?->toDateString(),
                'data_validade' => LegacyParser::toDate($row->data_validade ?? null)?->toDateString(),
                'legacy_arquivo' => LegacyParser::toStringOrNull($row->arquivo ?? null),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existente = CompdecAnexo::query()->where('legacy_id', $legacyId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = CompdecAnexo::query()->where('legacy_id', $legacyId)->first();
            $anexo = CompdecAnexo::query()->updateOrCreate(['legacy_id' => $legacyId], $payload);

            $this->copiarArquivoLegado($anexo, (string) ($row->arquivo ?? ''), $report);

            $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();
            $this->logEtl($legacyId, $anexo->id, $existente ? 'updated' : 'inserted', null, $row, false);
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    private function copiarArquivoLegado(CompdecAnexo $anexo, string $arquivoLegado, MigracaoReport $report): void
    {
        if ($arquivoLegado === '') {
            return;
        }

        $base = (string) config('compdec.legacy_paths.anexos', '');
        $path = rtrim($base, '/').'/'.ltrim($arquivoLegado, '/');

        if (! File::exists($path)) {
            $this->logEtl(
                $anexo->legacy_id,
                $anexo->id,
                'skipped',
                'arquivo_legado_inexistente: '.$path,
                null,
                false,
            );

            return;
        }

        if ($anexo->getFirstMedia(CompdecAnexo::MEDIA_ARQUIVO)) {
            $anexo->clearMediaCollection(CompdecAnexo::MEDIA_ARQUIVO);
        }

        $disk = (string) config('compdec.disk', 'compdec');

        $anexo
            ->addMedia($path)
            ->preservingOriginal()
            ->usingFileName(basename($path))
            ->usingName($anexo->titulo)
            ->toMediaCollection(CompdecAnexo::MEDIA_ARQUIVO, $disk);
    }

    private function mapearTipoLegado(object $row): string
    {
        $raw = mb_strtolower(trim((string) ($row->tipo ?? $row->categoria ?? '')));

        return match (true) {
            str_contains($raw, 'lei') => 'lei',
            str_contains($raw, 'decreto') => 'decreto',
            str_contains($raw, 'portaria') => 'portaria',
            str_contains($raw, 'regimento') => 'regimento',
            default => 'outros',
        };
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
            'recurso' => 'anexos',
            'legacy_table' => 'com_anexo',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
