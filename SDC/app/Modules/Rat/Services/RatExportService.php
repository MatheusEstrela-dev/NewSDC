<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatExportService
{
    public function exportToCsv(?Request $request = null): StreamedResponse
    {
        $filename = 'rat-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $query = RatOcorrencia::with(['relatosMorph.conteudo'])->orderByDesc('created_at');

        if ($request && $request->input('type') === 'period') {
            if ($inicio = $request->input('data_inicio')) {
                $query->whereDate('created_at', '>=', $inicio);
            }
            if ($fim = $request->input('data_fim')) {
                $query->whereDate('created_at', '<=', $fim);
            }
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
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

            $query->each(function (RatOcorrencia $oc) use ($handle) {
                $dadosGerais = $oc->relatosMorph
                    ->first(fn($r) => $r->conteudo instanceof RatRelatoDadosGerais)
                    ?->conteudo;

                fputcsv($handle, [
                    $oc->id,
                    $oc->numero_bos,
                    $oc->status === 1 ? 'Finalizado' : 'Rascunho',
                    $dadosGerais?->local_municipio ?? '',
                    $dadosGerais?->local_estadouf  ?? '',
                    $dadosGerais?->nat_nome_operacao ?? '',
                    $dadosGerais?->data_fato?->format('d/m/Y H:i') ?? '',
                    $dadosGerais?->data_inicio_atividade?->format('d/m/Y H:i') ?? '',
                    $dadosGerais?->data_termino_atividade?->format('d/m/Y H:i') ?? '',
                    $oc->created_at?->format('d/m/Y H:i'),
                ], ';');
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
