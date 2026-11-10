<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Modules\Rat\DTOs\RatRelatosDTO;
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

    public function createRat(RatRelatosDTO $relatos): RatOcorrencia
    {
        return $this->writeService->createWithData($relatos);
    }

    /**
     * Atualiza apenas campos editaveis. numero_bos e o protocolo (evidencia do
     * ranking) e status so muda pelo fluxo de finalizacao: numero_bos e
     * ignorado aqui; status=1 delega a RatWriteService::finalize (outbox incluso).
     */
    public function updateRat(string $id, array $data): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);

        if (($data['historico'] ?? null) !== null) {
            $ocorrencia->historico = $data['historico'];
            $ocorrencia->save();
        }

        if ((int) ($data['status'] ?? 0) === 1 && (int) $ocorrencia->status !== 1) {
            return $this->writeService->finalize($id);
        }

        return $ocorrencia->fresh();
    }

    public function deleteRat(string $id): void
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
