<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RatExportBIService
{
    public function listForApi(RatFilterDTO $filters): LengthAwarePaginator
    {
        return $this->buildQuery($filters)
            ->with(['relatosMorph.conteudo'])
            ->paginate($filters->perPage);
    }

    public function listForPowerBI(RatFilterDTO $filters): array
    {
        return $this->buildQuery($filters)
            ->with(['relatosMorph.conteudo'])
            ->get()
            ->map(fn($rat) => $this->flattenForBI($rat))
            ->toArray();
    }

    public function findById(string $id): ?RatOcorrencia
    {
        return RatOcorrencia::with(['relatosMorph.conteudo'])->find($id);
    }

    private function buildQuery(RatFilterDTO $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = RatOcorrencia::query()->orderByDesc('created_at');

        if ($filters->protocolo) {
            $query->where('numero_bos', 'like', '%' . $filters->protocolo . '%');
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->ano) {
            $query->whereYear('created_at', $filters->ano);
        }

        if ($filters->dataInicio) {
            $query->whereDate('created_at', '>=', $filters->dataInicio);
        }

        if ($filters->dataFim) {
            $query->whereDate('created_at', '<=', $filters->dataFim);
        }

        return $query;
    }

    private function flattenForBI(RatOcorrencia $rat): array
    {
        $dadosGerais = $rat->relatosMorph
            ->first(fn($r) => $r->conteudo instanceof \App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais)
            ?->conteudo;

        return array_filter([
            'rat_id'     => $rat->id,
            'protocolo'  => $rat->numero_bos,
            'status'     => $rat->status,
            'created_at' => $rat->created_at?->toIso8601String(),
            'updated_at' => $rat->updated_at?->toIso8601String(),
            'dados_gerais_data_fato'               => $dadosGerais?->data_fato,
            'dados_gerais_data_inicio_atividade'   => $dadosGerais?->data_inicio_atividade,
            'dados_gerais_data_termino_atividade'  => $dadosGerais?->data_termino_atividade,
        ], fn($v) => $v !== null);
    }
}
