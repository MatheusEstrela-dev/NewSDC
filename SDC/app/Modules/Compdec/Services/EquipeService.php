<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\DTOs\EquipeDTO;
use App\Modules\Compdec\Enums\FuncaoEquipe;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Support\LegacyParser;
use App\Modules\Compdec\Support\MigracaoReport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class EquipeService
{
    public function listarPorOrgao(int $orgaoId, int $perPage = 20): LengthAwarePaginator
    {
        return CompdecEquipe::query()
            ->doOrgao($orgaoId)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $orgaoId, int $equipeId): CompdecEquipe
    {
        return CompdecEquipe::query()
            ->doOrgao($orgaoId)
            ->findOrFail($equipeId);
    }

    public function criar(int $orgaoId, EquipeDTO $dto): CompdecEquipe
    {
        return DB::transaction(function () use ($orgaoId, $dto): CompdecEquipe {
            $orgao = Orgao::findOrFail($orgaoId);

            $this->validarCoordenadorUnicoAtivo($orgaoId, $dto, null);

            $payload = array_merge($dto->toArray(), ['orgao_id' => $orgao->id]);

            return CompdecEquipe::create($payload);
        });
    }

    public function atualizar(int $orgaoId, int $equipeId, EquipeDTO $dto): CompdecEquipe
    {
        return DB::transaction(function () use ($orgaoId, $equipeId, $dto): CompdecEquipe {
            $equipe = $this->obter($orgaoId, $equipeId);

            $this->validarCoordenadorUnicoAtivo($orgaoId, $dto, $equipeId);

            $equipe->update($dto->toArray());

            return $equipe->fresh();
        });
    }

    public function deletar(int $orgaoId, int $equipeId): bool
    {
        $equipe = $this->obter($orgaoId, $equipeId);

        return (bool) $equipe->delete();
    }

    public function restaurar(int $orgaoId, int $equipeId): bool
    {
        $equipe = CompdecEquipe::onlyTrashed()
            ->doOrgao($orgaoId)
            ->findOrFail($equipeId);

        return (bool) $equipe->restore();
    }

    /**
     * Regra de negocio: apenas UM coordenador ativo por orgao.
     */
    private function validarCoordenadorUnicoAtivo(int $orgaoId, EquipeDTO $dto, ?int $excludeId): void
    {
        if ($dto->funcao !== FuncaoEquipe::COORDENADOR->value || ! $dto->ativo) {
            return;
        }

        $existe = CompdecEquipe::query()
            ->doOrgao($orgaoId)
            ->coordenador()
            ->ativo()
            ->when($excludeId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();

        if ($existe) {
            throw new InvalidArgumentException(
                'Ja existe um coordenador ativo neste orgao. Inative o atual antes de criar outro.',
            );
        }
    }

    /* ============================================================
     * ETL: Migracao de com_eq_comdec -> compdec_equipes
     * ============================================================ */

    /**
     * Migra equipes do legado.
     *
     * com_eq_comdec.id_compdec (FK comentada no legado) e usado como lookup
     * em compdec_orgaos.legacy_id. Orfaos vao para o log com motivo
     * "orgao_legado_inexistente" e sao ignorados.
     */
    public function migrarLegado(int $chunk = 100, bool $dryRun = false): MigracaoReport
    {
        $report = new MigracaoReport('equipes');
        $report->dryRun = $dryRun;
        $connection = config('compdec.legacy_connection', 'legacy');

        DB::connection($connection)
            ->table('com_eq_comdec')
            ->orderBy('id')
            ->chunk($chunk, function ($linhas) use ($report, $dryRun): void {
                foreach ($linhas as $row) {
                    $this->migrarEquipeLegada($row, $report, $dryRun);
                }
            });

        return $report;
    }

    private function migrarEquipeLegada(object $row, MigracaoReport $report, bool $dryRun): void
    {
        $legacyId = LegacyParser::toIntOrNull($row->id ?? null);
        $legacyOrgaoId = LegacyParser::toIntOrNull($row->id_compdec ?? null);

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
                'nome' => LegacyParser::toStringOrNull($row->nome ?? null) ?? '(sem nome)',
                'funcao' => $this->mapearFuncaoLegado($row),
                'telefone' => LegacyParser::toStringOrNull($row->telefone ?? null),
                'celular' => LegacyParser::toStringOrNull($row->celular ?? null),
                'email' => LegacyParser::toStringOrNull($row->email ?? null),
                'cpf' => LegacyParser::toStringOrNull($row->cpf ?? null),
                'ativo' => LegacyParser::toBool($row->ativo ?? '1'),
                'ordem' => LegacyParser::toIntOrNull($row->ordem ?? null) ?? 0,
                'observacoes' => LegacyParser::toStringOrNull($row->observacoes ?? null),
                'legacy_id' => $legacyId,
            ];

            if ($dryRun) {
                $existente = CompdecEquipe::query()->where('legacy_id', $legacyId)->exists();
                $existente ? $report->registrarAtualizacao() : $report->registrarInsercao();

                return;
            }

            $existente = CompdecEquipe::query()->where('legacy_id', $legacyId)->first();
            $equipe = CompdecEquipe::query()->updateOrCreate(['legacy_id' => $legacyId], $payload);

            if ($existente) {
                $report->registrarAtualizacao();
                $this->logEtl($legacyId, $equipe->id, 'updated', null, $row, false);
            } else {
                $report->registrarInsercao();
                $this->logEtl($legacyId, $equipe->id, 'inserted', null, $row, false);
            }
        } catch (Throwable $e) {
            $report->registrarErro($legacyId, $e->getMessage());
            $this->logEtl($legacyId, null, 'error', $e->getMessage(), $row, $dryRun);
        }
    }

    private function mapearFuncaoLegado(object $row): string
    {
        $raw = mb_strtolower(trim((string) ($row->funcao ?? $row->cargo ?? '')));

        return match (true) {
            str_contains($raw, 'coordenador') => FuncaoEquipe::COORDENADOR->value,
            str_contains($raw, 'agente') => FuncaoEquipe::AGENTE->value,
            str_contains($raw, 'tecnico'), str_contains($raw, 'técnico') => FuncaoEquipe::TECNICO->value,
            str_contains($raw, 'apoio') => FuncaoEquipe::APOIO->value,
            default => FuncaoEquipe::OUTRO->value,
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
            'recurso' => 'equipes',
            'legacy_table' => 'com_eq_comdec',
            'legacy_id' => $legacyId ?? 0,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload !== null ? json_encode((array) $payload) : null,
            'created_at' => now(),
        ]);
    }
}
