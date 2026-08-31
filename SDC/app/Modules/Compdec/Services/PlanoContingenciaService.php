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
                // Data do envio. Sem ela o plano nasce com enviado_em NULL e,
                // como "NULL >= data" e NULL, o CASE de PlanCon\...\
                // expressaoSituacao() cai no ELSE e marca o plano recem-enviado
                // como IRREGULAR - alem de ordena-lo por ultimo (NULLS LAST) e
                // deixar a coluna de data em branco na tela.
                'enviado_em' => now(),
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
                // Arquivo novo e envio novo: a idade que define regular/irregular
                // passa a contar a partir de agora.
                $payload['enviado_em'] = now();
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
        $legacyOrgaoId = LegacyParser::toIntOrNull($row->compdec_id ?? null);

        if ($legacyOrgaoId === null) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'sem compdec_id', $row, $dryRun);

            return;
        }

        $orgao = Orgao::query()->where('legacy_id', $legacyOrgaoId)->first();

        if (! $orgao) {
            $report->registrarSkip();
            $this->logEtl($legacyId, null, 'skipped', 'orgao_legado_inexistente', $row, $dryRun);

            return;
        }

        try {
            // dt_upload e a unica data confiavel da linha: created_at/updated_at
            // so existem nos registros pos-2022 do legado.
            $enviadoEm = LegacyParser::toDate($row->dt_upload ?? null);

            $payload = [
                'orgao_id' => $orgao->id,
                // O legado grava a versao como '1', '2' ou vazio. Vazio vira v1
                // porque a coluna e NOT NULL e o registro e a unica versao.
                'versao' => $this->normalizarVersaoLegado($row->versao ?? null),
                'observacoes' => LegacyParser::toStringOrNull($row->obs ?? null),
                'ativo' => false, // marcado depois pelo passo marcarUltimoComoAtivo
                'tamanho_bytes' => LegacyParser::toIntOrNull($row->tamanho ?? null),
                'enviado_em' => $enviadoEm,
                'legacy_arquivo' => LegacyParser::toStringOrNull($row->file_plano ?? null),
                'legacy_id' => $legacyId,
                'legacy_municipio_id' => LegacyParser::toIntOrNull($row->id_municipio ?? null),
            ];

            if ($dryRun) {
                $existente = CompdecPlanoContingencia::query()->where('legacy_id', $legacyId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = CompdecPlanoContingencia::query()->where('legacy_id', $legacyId)->first();
            $plano = CompdecPlanoContingencia::query()->updateOrCreate(['legacy_id' => $legacyId], $payload);

            // Preserva a linha do tempo do legado: sem isto os 619 planos
            // nasceriam todos com a data do ETL.
            //
            // Precisa ser forceFill: `created_at` esta FORA de $fillable de
            // proposito (nao deve ser mass-assignable no fluxo normal do app),
            // entao passa-lo no payload do updateOrCreate era descartado em
            // silencio - justamente a data que este passo existe para manter.
            if ($enviadoEm !== null && ! $plano->created_at?->equalTo($enviadoEm)) {
                $plano->forceFill(['created_at' => $enviadoEm])->saveQuietly();
            }

            $this->copiarArquivoLegado($plano, (string) ($row->file_plano ?? ''));

            $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();
            $this->logEtl($legacyId, $plano->id, $existente ? 'updated' : 'inserted', null, $row, false);
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    /**
     * Procura o arquivo nas pastas dos dois sistemas que gravaram plano
     * (gestaocedec e sdc). Retorna o primeiro caminho existente, ou null.
     *
     * @see config('compdec.legacy_paths.planos')
     */
    public function localizarArquivoLegado(string $arquivoLegado): ?string
    {
        $bases = config('compdec.legacy_paths.planos', []);
        $bases = is_array($bases) ? $bases : [$bases];

        foreach ($bases as $base) {
            $base = trim((string) $base);

            if ($base === '') {
                continue;
            }

            $base = rtrim($base, '/');

            foreach ($this->candidatosDeNome($arquivoLegado) as $nome) {
                $path = $base.'/'.ltrim($nome, '/');

                if (File::exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Nomes a tentar para um mesmo registro do legado.
     *
     * Alem do nome exato, tenta a variante com a EXTENSAO DUPLICADA
     * (`Plano_...V.1.pdf.pdf`). O legado gravou assim em parte dos uploads --
     * o banco guarda `X.pdf` e o arquivo em disco chama `X.pdf.pdf` -- e sao
     * 8 planos que apareciam como 404 sem estarem perdidos de verdade.
     *
     * @return list<string>
     */
    private function candidatosDeNome(string $arquivo): array
    {
        $extensao = pathinfo($arquivo, PATHINFO_EXTENSION);

        return $extensao === ''
            ? [$arquivo]
            : [$arquivo, $arquivo.'.'.$extensao];
    }

    /**
     * com_plano_upload.versao vem como '1', '2' ou string vazia. Normaliza para
     * o formato usado no cadastro novo (v1, v2), mantendo texto ja livre.
     */
    private function normalizarVersaoLegado(mixed $versao): string
    {
        $texto = LegacyParser::toStringOrNull($versao);

        if ($texto === null) {
            return 'v1';
        }

        return ctype_digit($texto) ? "v{$texto}" : $texto;
    }

    private function copiarArquivoLegado(CompdecPlanoContingencia $plano, string $arquivoLegado): void
    {
        if ($arquivoLegado === '') {
            return;
        }

        $path = $this->localizarArquivoLegado($arquivoLegado);

        if ($path === null) {
            $this->logEtl(
                $plano->legacy_id,
                $plano->id,
                'skipped',
                'arquivo_legado_inexistente: '.$arquivoLegado,
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
        // Elege por data de upload, nao por MAX(id): a numeracao do legado nao
        // segue a cronologia (o id 1209 e de 2022-08 e o 1207 de 2022-02, mas
        // ha faixas antigas reaproveitadas). O id so desempata datas iguais.
        $ultimosIds = CompdecPlanoContingencia::query()
            ->select(DB::raw('DISTINCT ON (orgao_id) id'))
            ->whereNotNull('legacy_id')
            ->orderBy('orgao_id')
            ->orderByRaw('enviado_em DESC NULLS LAST')
            ->orderByDesc('id')
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
