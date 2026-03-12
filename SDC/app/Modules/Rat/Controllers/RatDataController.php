<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\DTO\RatFilterDTO;
use App\Modules\Rat\Resources\RatResource;
use App\Modules\Rat\Models\Rat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller de extração de dados do módulo RAT.
 * Responsabilidade única: endpoints de dados (JSON e export CSV).
 */
class RatDataController extends Controller
{
    /**
     * Retorna os dados completos de uma ocorrência em JSON.
     */
    public function showJson(int $id): JsonResponse
    {
        $ocorrencia = Rat::findOrFail($id);

        return response()->json((new RatResource($ocorrencia))->resolve());
    }

    /**
     * Gera e retorna download CSV com as ocorrências filtradas.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = RatFilterDTO::fromArray($request->only(['numero_bos', 'status', 'data_inicio', 'data_fim']));

        $query = Rat::orderBy('created_at', 'desc');

        if ($filters->numeroBos) {
            $query->where('numero_bos', 'like', '%' . $filters->numeroBos . '%');
        }
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }
        if ($filters->dataInicio) {
            $query->whereDate('created_at', '>=', $filters->dataInicio);
        }
        if ($filters->dataFim) {
            $query->whereDate('created_at', '<=', $filters->dataFim);
        }

        $ocorrencias = $query->get(['id', 'numero_bos', 'status', 'historico', 'prazo_edicao', 'created_at']);

        return response()->streamDownload(function () use ($ocorrencias) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Número BO', 'Status', 'Histórico', 'Prazo Edição', 'Criado em']);
            foreach ($ocorrencias as $oc) {
                fputcsv($handle, [
                    $oc->id,
                    $oc->numero_bos ?? '',
                    $oc->status_label,
                    $oc->historico ?? '',
                    $oc->prazo_edicao?->format('d/m/Y H:i') ?? '',
                    $oc->created_at?->format('d/m/Y H:i') ?? '',
                ]);
            }
            fclose($handle);
        }, 'rat-ocorrencias-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}