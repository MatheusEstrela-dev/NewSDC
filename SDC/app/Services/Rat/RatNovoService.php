<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Services\RatWriteService;

class RatNovoService
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    public function createRat(array $data): RatOcorrencia
    {
        return $this->writeService->createWithData($data);
    }

    public function updateRat(int $id, array $data): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->fill(array_filter([
            'numero_bos' => $data['numero_bos'] ?? null,
            'status'     => $data['status']     ?? null,
        ], fn($v) => $v !== null));
        $ocorrencia->save();

        return $ocorrencia->fresh();
    }

    public function deleteRat(int $id): void
    {
        RatOcorrencia::findOrFail($id)->delete();
    }

    public function extractDadosGerais(RatOcorrencia $ocorrencia): array
    {
        $relato = $ocorrencia->relatosMorph
            ->first(fn($r) => $r->conteudo instanceof RatRelatoDadosGerais);

        return $relato?->conteudo?->toArray() ?? [];
    }

    public function extractEnvolvidos(RatOcorrencia $ocorrencia): array
    {
        return $ocorrencia->relatosMorph
            ->filter(fn($r) => $r->conteudo instanceof RatRelatoEnvolvidos)
            ->map(fn($r) => $r->conteudo?->toArray())
            ->values()
            ->toArray();
    }

    public function extractRecursos(RatOcorrencia $ocorrencia): array
    {
        return $ocorrencia->relatosMorph
            ->filter(fn($r) => $r->conteudo instanceof RatRelatoRecurso)
            ->map(fn($r) => $r->conteudo?->toArray())
            ->values()
            ->toArray();
    }
}
