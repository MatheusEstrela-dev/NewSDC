<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\AtaDTO;
use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Support\VigenciaAta;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AtaService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Ata::query()
            ->withCount('lotes')
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['vigente'] ?? null, fn ($q) => $q->vigente())
            // Filtro por situacao (vigente/vencida/agendada/inativa). Valor
            // invalido nao filtra — a traducao para scope fica em Ata::scopeSituacao.
            ->when($filtros['situacao'] ?? null, fn ($q, $s) => $q->situacao((string) $s))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderByDesc('dt_inicio')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = Ata::query()
            ->withCount('lotes')
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['vigente'] ?? null, fn ($q) => $q->vigente())
            ->when($filtros['situacao'] ?? null, fn ($q, $s) => $q->situacao((string) $s))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderByDesc('dt_inicio')
            ->get();

        return $rows->map(function (Ata $a): array {
            // A coluna Situacao agora distingue Vencida de Ativa: antes o CSV
            // rotulava toda ata ligada e fora do prazo como "Ativa".
            return [
                'Numero'          => $a->numero,
                'Vigencia Inicio' => $a->dt_inicio?->format('d/m/Y'),
                'Vigencia Fim'    => $a->dt_final?->format('d/m/Y'),
                'Situacao'        => $a->situacao->label(),
                'Dias Restantes'  => $a->dias_restantes,
                'Ativo'           => $a->ativo ? 'Sim' : 'Nao',
                'Lotes'           => (int) $a->lotes_count,
                'Historico'       => $a->historico,
                'Observacoes'     => $a->observacoes,
            ];
        })->all();
    }

    public function obter(int $id): Ata
    {
        return Ata::query()
            ->withCount('lotes')
            ->with(['lotes' => fn ($q) => $q->with(['municipios:id,nome,uf', 'prestador:id,nome,cnpj'])])
            ->findOrFail($id);
    }

    public function criar(AtaDTO $dto): Ata
    {
        return DB::transaction(fn () => Ata::create($dto->toArray()));
    }

    public function atualizar(int $id, AtaDTO $dto): Ata
    {
        return DB::transaction(function () use ($id, $dto): Ata {
            $ata = Ata::findOrFail($id);
            $ata->update($dto->toArray());

            return $ata->fresh();
        });
    }

    public function deletar(int $id): bool
    {
        $ata = Ata::query()->withCount('lotes')->findOrFail($id);

        if ($ata->lotes_count > 0) {
            throw new \DomainException(
                "Ata {$ata->numero} possui {$ata->lotes_count} lote(s) vinculado(s). Remova-os antes."
            );
        }

        return (bool) $ata->delete();
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $hoje = now()->toDateString();
        $limiteAlerta = now()->addDays(VigenciaAta::JANELA_PROXIMO_VENCER_DIAS)->toDateString();

        // Um unico round-trip para todos os contadores: `COUNT(*) FILTER (WHERE ...)`
        // e sintaxe Postgres e evita cinco ->count() separados.
        $row = Ata::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COUNT(*) FILTER (WHERE ativo = FALSE) AS inativos,
                COUNT(*) FILTER (WHERE ativo = TRUE AND dt_inicio <= ? AND dt_final >= ?) AS vigentes,
                COUNT(*) FILTER (WHERE ativo = TRUE AND dt_final < ?) AS vencidas,
                COUNT(*) FILTER (WHERE ativo = TRUE AND dt_inicio > ?) AS agendadas,
                COUNT(*) FILTER (WHERE ativo = TRUE AND dt_inicio <= ? AND dt_final >= ? AND dt_final <= ?) AS a_vencer
            ', [$hoje, $hoje, $hoje, $hoje, $hoje, $hoje, $limiteAlerta])
            ->first();

        $vencidas = (int) ($row->vencidas ?? 0);

        return [
            'total'      => (int) ($row->total ?? 0),
            'ativos'     => (int) ($row->ativos ?? 0),
            'inativos'   => (int) ($row->inativos ?? 0),
            'vigentes'   => (int) ($row->vigentes ?? 0),
            'vencidas'   => $vencidas,
            'agendadas'  => (int) ($row->agendadas ?? 0),
            'a_vencer'   => (int) ($row->a_vencer ?? 0),
            // Chave antiga mantida (mesma contagem de `vencidas`) para nao
            // quebrar consumidores que ainda leem `encerradas`.
            'encerradas' => $vencidas,
        ];
    }
}
