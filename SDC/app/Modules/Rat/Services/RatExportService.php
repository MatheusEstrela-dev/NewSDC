<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatExportService
{
    /**
     * Export sincrono — envia CSV direto na response via streamDownload.
     * Mantido para backwards-compat; novas integracoes devem usar a rota
     * /export/async que delega ao job (ver RatExportToCsvJob).
     */
    public function exportToCsv(?Request $request = null): StreamedResponse
    {
        $filename = 'rat-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            $this->writeCsvTo($handle, $this->extractFilters($request));
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Escreve CSV no handle aberto (pode ser file pointer, php://output, etc.).
     * Reusado pela rota sincrona e pelo job assincrono.
     *
     * @param resource $handle
     * @param array<string, mixed> $filters
     */
    public function writeCsvTo($handle, array $filters): void
    {
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($handle, [
            'ID',
            'Protocolo',
            'Status',
            'Município',
            'UF',
            'Natureza',
            'Data do Fato',
            'Início Atividade',
            'Término Atividade',
            'Criado em',
        ], ';');

        $query = RatOcorrencia::with(['relatosMorph.conteudo'])->orderByDesc('created_at');

        if (($filters['type'] ?? null) === 'period') {
            if (!empty($filters['data_inicio'])) {
                $query->whereDate('created_at', '>=', $filters['data_inicio']);
            }
            if (!empty($filters['data_fim'])) {
                $query->whereDate('created_at', '<=', $filters['data_fim']);
            }
        }

        $query->each(function (RatOcorrencia $oc) use ($handle): void {
            $dadosGerais = $oc->relatosMorph
                ->first(fn ($r) => $r->conteudo instanceof RatRelatoDadosGerais)
                ?->conteudo;

            fputcsv($handle, [
                $oc->id,
                $oc->numero_bos,
                $oc->status === 1 ? 'Finalizado' : 'Rascunho',
                $dadosGerais?->local_municipio ?? '',
                $dadosGerais?->local_estadouf ?? '',
                $dadosGerais?->nat_nome_operacao ?? '',
                $dadosGerais?->data_fato?->format('d/m/Y H:i') ?? '',
                $dadosGerais?->data_inicio_atividade?->format('d/m/Y H:i') ?? '',
                $dadosGerais?->data_termino_atividade?->format('d/m/Y H:i') ?? '',
                $oc->created_at?->format('d/m/Y H:i'),
            ], ';');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(?Request $request): array
    {
        if ($request === null) {
            return [];
        }

        return [
            'type' => $request->input('type'),
            'data_inicio' => $request->input('data_inicio'),
            'data_fim' => $request->input('data_fim'),
        ];
    }
}
