<?php

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaeProtocoloController extends Controller
{
    public function __construct(
        private readonly PaeProtocoloService $service,
        private readonly CsvExportService $csvExportService
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $filters = $request->only(['search', 'status', 'analista_id', 'data_inicio', 'data_fim']);

        $protocolos = $this->service->list($filters);
        $statistics = $this->service->getStatistics();

        return \Inertia\Inertia::render('PaeProtocolosIndex', [
            'protocolos' => $protocolos,
            'statistics' => $statistics,
            'filters' => $filters,
            'statusOptions' => PaeProtocoloStatus::toSelectArray(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'num_protocolo' => ['required', 'string', 'max:50', 'unique:pae_protocolos,num_protocolo'],
            'sigibar' => ['nullable', 'string', 'max:100'],
            'sei_numero' => ['nullable', 'string', 'max:100'],
            'pae_empnto_id' => ['nullable', 'integer', 'exists:pae_empntos,id'],
            'obs' => ['nullable', 'string'],
        ]);

        $protocolo = $this->service->create($data, $request->user());

        return redirect()->route('pae.protocolos.index')
            ->with('success', "Protocolo {$protocolo->num_protocolo} criado com sucesso.");
    }

    public function changeStatus(Request $request, PaeProtocolo $protocolo): JsonResponse
    {
        $data = $request->validate([
            'novo_status' => ['required', 'string', Rule::in(array_keys(PaeProtocoloStatus::toSelectArray()))],
            'obs' => ['nullable', 'string', 'max:500'],
        ]);

        $novoStatus = PaeProtocoloStatus::from($data['novo_status']);

        $protocolo = $this->service->changeStatus(
            $protocolo,
            $novoStatus,
            $request->user(),
            $data['obs'] ?? ''
        );

        return response()->json([
            'message' => "Status atualizado para {$novoStatus->getLabel()}.",
            'protocolo' => [
                'id' => $protocolo->id,
                'status' => $protocolo->status->value,
                'status_label' => $protocolo->status->getLabel(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'status', 'analista_id', 'data_inicio', 'data_fim']);
        $protocolos = $this->service->list($filters, 1000)->items();

        $headers = ['ID', 'Numero', 'Status', 'Analista', 'Data Entrada', 'Limite Análise'];

        $mapper = fn($p) => [
            $p->id,
            $p->num_protocolo,
            $p->status->getLabel(),
            $p->analistaAtual?->name ?? '—',
            $p->dt_entrada?->format('d/m/Y') ?? '—',
            $p->limite_analise?->format('d/m/Y') ?? '—',
        ];

        return $this->csvExportService->export($protocolos, $headers, $mapper, 'pae_protocolos');
    }
}
