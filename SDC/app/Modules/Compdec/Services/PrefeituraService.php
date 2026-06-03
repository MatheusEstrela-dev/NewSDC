<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Models\Prefeitura;
use App\Modules\Compdec\Support\LegacyParser;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class PrefeituraService
{
    public function obterPorOrgao(int $orgaoId): ?Prefeitura
    {
        $orgao = Orgao::findOrFail($orgaoId);

        if (! $orgao->municipio_id) {
            return null;
        }

        return Prefeitura::query()
            ->where('municipio_id', $orgao->municipio_id)
            ->first();
    }

    public function upsertPorOrgao(int $orgaoId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($orgaoId, $dto): Prefeitura {
            $orgao = Orgao::findOrFail($orgaoId);

            if (! $orgao->municipio_id) {
                throw new InvalidArgumentException('Orgao nao possui municipio vinculado; nao e possivel criar prefeitura.');
            }

            // garante que o DTO traz o municipio_id correto do orgao
            $payload = $dto->toArray();
            $payload['municipio_id'] = $orgao->municipio_id;

            return Prefeitura::updateOrCreate(
                ['municipio_id' => $orgao->municipio_id],
                $payload,
            );
        });
    }

    public function uploadFoto(int $prefeituraId, UploadedFile $arquivo): Media
    {
        $prefeitura = Prefeitura::findOrFail($prefeituraId);

        return $prefeitura
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
    }

    public function removerFoto(int $prefeituraId): bool
    {
        $prefeitura = Prefeitura::findOrFail($prefeituraId);
        $prefeitura->clearMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO);

        return true;
    }

    public function obterPrefeituraPorOrgaoOuFalhar(int $orgaoId): Prefeitura
    {
        $prefeitura = $this->obterPorOrgao($orgaoId);

        if (! $prefeitura) {
            throw new ModelNotFoundException('Prefeitura nao cadastrada para este orgao.');
        }

        return $prefeitura;
    }

    /* ============================================================
     * ETL: Migracao do legado para compdec_prefeituras
     * ============================================================ */

    /**
     * Migra prefeituras do legado:
     *   - prefeito_* vem de com_comdec
     *   - geo + foto vem de cedec_prefeitura
     *   - Uniao por municipio_id; resultado em compdec_prefeituras
     */
    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('prefeituras');
        $report->dryRun = $dryRun;
        $connection = config('compdec.legacy_connection', 'legacy');

        DB::connection($connection)
            ->table('com_comdec as c')
            ->leftJoin('cedec_prefeitura as p', 'p.municipio_id', '=', 'c.municipio_id')
            ->select(
                'c.id_comdec as legacy_id',
                'c.municipio_id',
                'c.prefeito_nome', 'c.prefeito_telefone', 'c.prefeito_celular', 'c.prefeito_email',
                'c.inss_tem_cobranca', 'c.inss_aliquota', 'c.inss_lei_cobranca', 'c.inss_responsavel',
                'p.endereco', 'p.bairro', 'p.cep',
                'p.latitude', 'p.longitude',
            )
            ->orderBy('c.id_comdec')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                foreach ($linhas as $row) {
                    $this->migrarPrefeituraLegada($row, $report, $dryRun);
                }
            });

        return $report;
    }

    private function migrarPrefeituraLegada(object $row, MigracaoReport $report, bool $dryRun): void
    {
        $legacyId = LegacyParser::toIntOrNull($row->legacy_id ?? null);
        $municipioId = LegacyParser::toIntOrNull($row->municipio_id ?? null);

        if ($municipioId === null) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'sem municipio_id', $row, $dryRun);

            return;
        }

        try {
            $payload = [
                'municipio_id' => $municipioId,
                'prefeito_nome' => LegacyParser::toStringOrNull($row->prefeito_nome ?? null),
                'prefeito_telefone' => LegacyParser::toStringOrNull($row->prefeito_telefone ?? null),
                'prefeito_celular' => LegacyParser::toStringOrNull($row->prefeito_celular ?? null),
                'prefeito_email' => LegacyParser::toStringOrNull($row->prefeito_email ?? null),
                'endereco' => LegacyParser::toStringOrNull($row->endereco ?? null),
                'bairro' => LegacyParser::toStringOrNull($row->bairro ?? null),
                'cep' => LegacyParser::toStringOrNull($row->cep ?? null),
                'latitude' => isset($row->latitude) ? LegacyParser::toDecimalBR($row->latitude) : null,
                'longitude' => isset($row->longitude) ? LegacyParser::toDecimalBR($row->longitude) : null,
                'inss_tem_cobranca' => LegacyParser::toBool($row->inss_tem_cobranca ?? null),
                'inss_aliquota' => isset($row->inss_aliquota) ? LegacyParser::toDecimalBR($row->inss_aliquota) : null,
                'inss_lei_cobranca' => LegacyParser::toStringOrNull($row->inss_lei_cobranca ?? null),
                'inss_responsavel' => LegacyParser::toStringOrNull($row->inss_responsavel ?? null),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existente = Prefeitura::query()->where('municipio_id', $municipioId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = Prefeitura::query()->where('municipio_id', $municipioId)->first();
            $prefeitura = Prefeitura::query()->updateOrCreate(['municipio_id' => $municipioId], $payload);

            if ($existente) {
                $report->registrarAtualizacao();
                $this->logEtl($legacyId, $prefeitura->id, 'updated', null, $row, false);
            } else {
                $report->registrarInsercao();
                $this->logEtl($legacyId, $prefeitura->id, 'inserted', null, $row, false);
            }
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
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
            'recurso' => 'prefeituras',
            'legacy_table' => 'com_comdec+cedec_prefeitura',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
