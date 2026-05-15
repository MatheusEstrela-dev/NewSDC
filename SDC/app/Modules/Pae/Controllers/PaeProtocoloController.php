<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $user          = $request->user();
        $rolesGestores = ['Gestor', 'Administrador', 'admin', 'super-admin', 'Desenvolvedor'];
        $podeVerTodos  = $user->hasAnyRole($rolesGestores);
        $canAtribuir   = $user->hasAnyRole($rolesGestores);
        $canCreate     = $user->can('pae.protocolos.create');
        $canEdit       = $user->can('pae.protocolos.edit');
        $canDelete     = $user->can('pae.protocolos.delete');
        $canExport     = $user->can('pae.protocolos.export');
        $canCheck      = $canEdit;
        $canPdf        = $user->can('pae.protocolos.view');

        if (!$podeVerTodos) {
            $filters['restringir_ao_analista'] = $user->id;
        }

        $protocolos = $this->service->list($filters);
        $statistics = $this->service->getStatistics($podeVerTodos ? null : $user->id);

        $analistas = DB::table('users')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])
            ->prepend(['value' => '', 'label' => 'Todos os analistas'])
            ->values();

        $empreendedores = \Illuminate\Support\Facades\DB::table('pae_empdors')
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->map(fn ($e) => ['value' => (string) $e->id, 'label' => $e->nome])
            ->prepend(['value' => '', 'label' => 'Todos os empreendedores'])
            ->values();

        return \Inertia\Inertia::render('PaeProtocolosIndex', [
            'protocolos'     => $protocolos,
            'statistics'     => $statistics,
            'filters'        => $filters,
            'statusOptions'  => PaeProtocoloStatus::toSelectArray(),
            'analistas'      => $analistas,
            'empreendedores' => $empreendedores,
            'canCreate'      => $canCreate,
            'canEdit'        => $canEdit,
            'canDelete'      => $canDelete,
            'canExport'      => $canExport,
            'canAtribuir'    => $canAtribuir,
            'canCheck'       => $canCheck,
            'canPdf'         => $canPdf,
            'podeVerTodos'   => $podeVerTodos,
        ]);
    }

    public function proximoNumero(): JsonResponse
    {
        return response()->json([
            'num_protocolo' => $this->service->gerarNumProtocolo(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'sigibar'       => ['nullable', 'string', 'max:100'],
            'sei_numero'    => ['nullable', 'string', 'max:100'],
            'pae_empnto_id' => ['nullable', 'integer', 'exists:pae_empntos,id'],
            'obs'           => ['nullable', 'string'],
        ]);

        $protocolo = $this->service->create($data, $request->user());

        return redirect()->route('pae.index', ['protocolo_id' => $protocolo->id])
            ->with('success', "Protocolo {$protocolo->num_protocolo} criado com sucesso.");
    }

    public function historico(PaeProtocolo $paeProtocolo): JsonResponse
    {
        $protocolo = $paeProtocolo->load(['timeline.usuario', 'tramitacoes.usuario']);

        $eventoMap = [
            'criacao'        => ['tipo' => 'criacao',      'titulo' => 'Protocolo Criado'],
            'status_alterado'=> ['tipo' => 'status',       'titulo' => 'Status Alterado'],
            'atribuicao'     => ['tipo' => 'atribuicao',   'titulo' => 'Analista Atribuído'],
            'notificacao'    => ['tipo' => 'notificacao',  'titulo' => 'Notificação Enviada'],
            'analise'        => ['tipo' => 'analise',      'titulo' => 'Análise Realizada'],
            'edicao'         => ['tipo' => 'edicao',       'titulo' => 'Protocolo Atualizado'],
        ];

        $timeline = $protocolo->timeline->map(function ($item) use ($eventoMap) {
            $map = $eventoMap[$item->evento] ?? ['tipo' => $item->evento, 'titulo' => ucfirst($item->evento)];
            return [
                'id'          => $item->id,
                'tipo'        => $map['tipo'],
                'titulo'      => $map['titulo'],
                'descricao'   => $item->descricao,
                'dataISO'     => $item->created_at?->toIso8601String(),
                'data'        => $item->created_at?->format('d/m/Y, H:i'),
                'responsavel' => $item->usuario?->name ?? 'Sistema',
            ];
        });

        $analises = $protocolo->tramitacoes->map(fn ($item) => [
            'id'          => $item->id,
            'titulo'      => $item->status ? 'Movimentacao de Status' : 'Analise Registrada',
            'data'        => $item->dt_status?->format('d/m/Y, H:i'),
            'responsavel' => $item->usuario?->name ?? 'Sistema',
            'status'      => $item->status,
            'descricao'   => $item->obs,
        ])->values();

        $notificacoes = $protocolo->tramitacoes
            ->where('status', PaeProtocoloStatus::NOTIFICACAO->value)
            ->map(fn ($item) => [
                'id'          => $item->id,
                'titulo'      => 'Notificacao registrada',
                'data'        => $item->dt_status?->format('d/m/Y, H:i'),
                'responsavel' => $item->usuario?->name ?? 'Sistema',
                'canal'       => 'Sistema',
                'descricao'   => $item->obs,
            ])
            ->values();

        return response()->json([
            'protocolo'    => $protocolo->num_protocolo,
            'analises'     => $analises,
            'notificacoes' => $notificacoes,
            'timeline'     => $timeline,
        ]);
    }

    public function changeStatus(Request $request, PaeProtocolo $paeProtocolo): JsonResponse
    {
        $data = $request->validate([
            'novo_status' => ['required', 'string', Rule::in(array_keys(PaeProtocoloStatus::toSelectArray()))],
            'obs' => ['nullable', 'string', 'max:500'],
        ]);

        $novoStatus = PaeProtocoloStatus::from($data['novo_status']);

        $paeProtocolo = $this->service->changeStatus(
            $paeProtocolo,
            $novoStatus,
            $request->user(),
            $data['obs'] ?? ''
        );

        return response()->json([
            'message' => "Status atualizado para {$novoStatus->getLabel()}.",
            'protocolo' => [
                'id' => $paeProtocolo->id,
                'status' => $paeProtocolo->status->value,
                'status_label' => $paeProtocolo->status->getLabel(),
            ],
        ]);
    }

    public function assign(Request $request, PaeProtocolo $paeProtocolo): \Illuminate\Http\RedirectResponse
    {
        if (!$paeProtocolo->status->isAssignableStatus()) {
            abort(422, 'Protocolo nao pode ser atribuido no status atual.');
        }

        $data = $request->validate([
            'analista_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $analista = \App\Models\User::findOrFail($data['analista_id']);

        $this->service->atribuir($paeProtocolo, $analista, $request->user());

        return redirect()->back()->with('success', "Analista {$analista->name} atribuído com sucesso.");
    }

    public function destroy(PaeProtocolo $paeProtocolo): \Illuminate\Http\RedirectResponse
    {
        $this->service->delete($paeProtocolo);

        return redirect()->back()->with('success', 'Protocolo arquivado/excluído com sucesso.');
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
