<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Modules\Rat\DTOs\RatBoDTO;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatHistoricoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\Http\Requests\RatDadosGeraisRequest;
use App\Modules\Rat\Http\Requests\RatEnvolvidoRequest;
use App\Modules\Rat\Http\Requests\RatHistoricoRequest;
use App\Modules\Rat\Http\Requests\RatRecursoRequest;
use App\Modules\Rat\Http\Requests\RatVistoriaRequest;
use App\Modules\Rat\Http\Resources\RatResource as RatOcorrenciaResource;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\RatOcorrenciaHistorico;
use App\Modules\Rat\Models\RatOcorrenciaRelato;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use App\Http\Controllers\Traits\AsynchronousResponse;
use App\Jobs\Rat\ExportRatToCsvJob;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatHistoricoService;
use App\Modules\Rat\Services\RatOcorrenciaService;
use App\Modules\Rat\Services\RatRelatoService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatUnifiedController extends BaseController
{
    use AuthorizesRequests;
    use AsynchronousResponse;
    use ValidatesRequests;

    public function __construct(
        private readonly RatWriteService      $writeService,
        private readonly RatAttachmentService $attachmentService,
        private readonly RatExportService     $exportService,
        private readonly RatOcorrenciaService $ocorrenciaService,
        private readonly RatRelatoService     $relatoService,
        private readonly RatHistoricoService  $historicoService,
    ) {}

    // =========================================================================
    // WEB — Inertia
    // =========================================================================

    public function index(Request $request): Response
    {
        $rats = RatOcorrencia::with(['creator'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $statistics = [
            'total'    => RatOcorrencia::count(),
            'hoje'     => RatOcorrencia::whereDate('created_at', today())->count(),
            'esteMes'  => RatOcorrencia::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'esteAno'  => RatOcorrencia::whereYear('created_at', now()->year)->count(),
        ];

        return Inertia::render('RatIndex', [
            'rats'       => RatOcorrenciaResource::collection($rats),
            'statistics' => $statistics,
            'filters'    => [],
        ]);
    }

    public function create(): Response
    {
        // Render create page - NO database operations here
        // The form will use POST /rat to store data
        return Inertia::render('Rat/RatCreate');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $traceId = $this->ratDebugTraceId($request);

        try {
            $this->ratDebug($request, $traceId, 'controller.store.received', [
                'route' => 'rat.store',
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'expects_json' => $request->expectsJson() || $request->wantsJson(),
                'input_keys' => array_keys($request->all()),
                'payload' => $this->ratDebugPayload($request->all()),
            ]);

            $validated = $request->validate([
                'dadosGerais' => 'nullable|array',
                'comunicacao' => 'nullable|array',
                'local'       => 'nullable|array',
                'endereco'    => 'nullable|array',
                'recursos'    => 'nullable|array',
                'envolvidos'  => 'nullable|array',
                'vistoria'    => 'nullable|array',
                'historico'   => 'nullable',
                'finalize'    => 'nullable|boolean',
            ]);

            $this->ratDebug($request, $traceId, 'controller.store.validated', [
                'validated_keys' => array_keys($validated),
                'payload' => $this->ratDebugPayload($validated),
            ]);

            $ocorrencia = $this->writeService->createWithData($validated);

            $this->ratDebug($request, $traceId, 'controller.store.persisted', [
                'rat_id' => $ocorrencia->id,
                'numero_bos' => $ocorrencia->numero_bos,
                'status' => $ocorrencia->status,
                'created_by' => $ocorrencia->created_by,
                'updated_by' => $ocorrencia->updated_by,
                'created_at' => $ocorrencia->created_at?->toISOString(),
                'db_counts' => [
                    'rat_ocorrencias' => RatOcorrencia::whereKey($ocorrencia->id)->count(),
                    'rat_ocorrencia_relatos' => RatOcorrenciaRelato::where('ocorrencia_id', $ocorrencia->id)->count(),
                    'rat_relato_dados_gerais' => RatRelatoDadosGerais::where('ocorrencia_id', $ocorrencia->id)->count(),
                    'rat_relato_recursos' => RatRelatoRecurso::where('ocorrencia_id', $ocorrencia->id)->count(),
                    'rat_relato_envolvidos' => RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrencia->id)->count(),
                    'rat_relato_vistoria' => RatRelatoVistoria::where('ocorrencia_id', $ocorrencia->id)->count(),
                ],
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success'    => true,
                    'id'         => $ocorrencia->id,
                    'numero_bos' => $ocorrencia->numero_bos,
                    'message'    => 'RAT criado com sucesso.',
                ], 201);
            }

            return redirect()
                ->to($this->boUrl($ocorrencia->id))
                ->with('success', 'Ocorrência RAT criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar RAT: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao criar RAT: ' . $e->getMessage()])
                ->withInput();
        }
    }

    private function boUrl(string $id, ?string $aba = null): string
    {
        $url = route('rat.edit', $id);
        if ($aba) {
            $url .= '?aba=' . $aba;
        }
        return $url;
    }

    private function ratDebugTraceId(Request $request): string
    {
        return (string) ($request->headers->get('X-Request-Id') ?: str()->uuid());
    }

    private function ratDebug(Request $request, string $traceId, string $stage, array $context = []): void
    {
        if (!$this->ratDebugEnabled($request)) {
            return;
        }

        Log::debug('[RAT_DEBUG_CREATE] ' . $stage, array_merge([
            'trace_id' => $traceId,
            'user_id' => Auth::id(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
        ], $context));
    }

    private function ratDebugEnabled(Request $request): bool
    {
        return (bool) config('app.debug') || $request->input('_debug') === 'rat' || $request->query('debug') === 'rat';
    }

    private function ratDebugPayload(array $payload): array
    {
        return Arr::except($payload, [
            '_token',
            'password',
            'password_confirmation',
            'current_password',
        ]);
    }

    public function show(string $id): Response
    {
        $ocorrencia = $this->writeService->findById($id);
        abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

        return Inertia::render('Rat', [
            'rat'      => (new RatOcorrenciaResource($ocorrencia))->resolve(),
            'viewOnly' => true,
        ]);
    }

    public function edit(string $id): Response
    {
        $ocorrencia = $this->writeService->findById($id);
        abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

        try {
            $ratData = (new RatOcorrenciaResource($ocorrencia))->resolve();
        } catch (\Throwable $e) {
            Log::error('Error resolving RatOcorrenciaResource in edit()', [
                'ocorrencia_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Fallback to minimal data if resource resolution fails
            $ratData = [
                'id' => $ocorrencia->id,
                'status' => $ocorrencia->status,
                'numero_bos' => $ocorrencia->numero_bos ?? null,
                'protocolo' => $ocorrencia->protocolo ?? null,
            ];
        }

        return Inertia::render('Rat/RatEdit', [
            'rat'        => $ratData,
            'lastUpdate' => $ocorrencia->updated_at?->toIso8601String(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
            if ($request->has('dadosGerais') || $request->has('comunicacao') || $request->has('local') || $request->has('endereco')) {
                $this->writeService->saveDadosGerais($id, RatDadosGeraisDTO::fromArray($request->all()));
            }

            if ($request->has('recursos')) {
                RatRelatoRecurso::where('ocorrencia_id', $id)->delete();
                RatOcorrenciaRelato::where('ocorrencia_id', $id)
                    ->where('conteudo_type', RatRelatoRecurso::class)
                    ->forceDelete();

                foreach ($request->input('recursos', []) as $index => $recursoData) {
                    unset($recursoData['id']);
                    $recursoData['seq'] = $recursoData['seq'] ?? ($index + 1);
                    $this->writeService->saveRecurso($id, RatRecursoDTO::fromArray($recursoData));
                }
            }

            if ($request->has('envolvidos')) {
                RatRelatoEnvolvidos::where('ocorrencia_id', $id)->delete();
                RatOcorrenciaRelato::where('ocorrencia_id', $id)
                    ->where('conteudo_type', RatRelatoEnvolvidos::class)
                    ->forceDelete();

                foreach ($request->input('envolvidos', []) as $index => $envolvidoData) {
                    unset($envolvidoData['id']);
                    $envolvidoData['seq'] = $envolvidoData['seq'] ?? ($index + 1);
                    $this->writeService->saveEnvolvido($id, RatEnvolvidoDTO::fromArray($envolvidoData));
                }
            }

            if ($request->has('vistoria') && !empty($request->input('vistoria'))) {
                $this->writeService->saveVistoria($id, RatVistoriaDTO::fromArray($request->input('vistoria')));
            }

            if ($request->has('historico')) {
                $this->writeService->saveHistorico($id, RatHistoricoDTO::fromArray(['historico' => $request->input('historico')]));
            }

            RatOcorrencia::where('id', $id)->update(['updated_by' => Auth::id()]);
        });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ocorrência atualizada com sucesso.']);
        }

        return redirect()
            ->to($this->boUrl($id))
            ->with('success', 'Ocorrência atualizada com sucesso!');
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        try {
            RatOcorrencia::findOrFail($id)->delete();
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir RAT', ['id' => $id, 'error' => $e->getMessage()]);
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()], 500);
            }
            return redirect()->route('rat.index')->with('error', 'Erro ao excluir RAT.');
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ocorrência excluída com sucesso.']);
        }

        return redirect()
            ->route('rat.index')
            ->with('success', 'Ocorrência excluída com sucesso!');
    }

    public function finalize(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $this->writeService->finalize($id);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'RAT finalizado com sucesso.']);
        }

        return redirect()
            ->route('rat.show', $id)
            ->with('success', 'Ocorrência finalizada com sucesso!');
    }

    public function draft(Request $request, string $id): RedirectResponse|JsonResponse
    {
        try {
            $ocorrencia = $this->writeService->saveDraft($id, $request->all());
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar rascunho RAT', ['id' => $id, 'error' => $e->getMessage()]);
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()], 500);
            }
            return redirect()->to($this->boUrl($id))->with('error', 'Erro ao salvar rascunho.');
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Rascunho salvo com sucesso!',
                'numero_bos' => $ocorrencia?->numero_bos,
                'protocolo'  => $ocorrencia?->protocolo,
            ]);
        }

        return redirect()
            ->to($this->boUrl($id))
            ->with('success', 'Rascunho salvo com sucesso!');
    }

    // =========================================================================
    // API — Boletim de Ocorrência
    // =========================================================================

    public function indexBo(Request $request): Response
    {
        $ocorrenciaId = $request->query('ocorrencia_id');

        if ($ocorrenciaId) {
            $ocorrencia = $this->writeService->findById($ocorrenciaId);
            abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

            $isNew = !RatRelatoDadosGerais::where('ocorrencia_id', $ocorrenciaId)->exists();

            return Inertia::render('Rat', [
                'rat'      => (new RatOcorrenciaResource($ocorrencia))->resolve(),
                'viewOnly' => false,
                'isCreate' => $isNew,
            ]);
        }

        $bos = RatRelatoDadosGerais::with(['ocorrencia'])->paginate(15);

        return Inertia::render('Rat/BoIndex', ['bos' => $bos]);
    }

    public function storeBo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'prazo_edicao' => 'nullable|date',
            'status'       => 'nullable|integer',
        ]);

        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(RatBoDTO::fromArray($validated));

        return response()->json([
            'success' => true,
            'message' => "BO {$ocorrencia->numero_bos} registrado com sucesso!",
            'data'    => $ocorrencia,
        ], 201);
    }

    public function showBo(string $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail((int) $id);

        return response()->json(['success' => true, 'data' => $ocorrencia]);
    }

    // =========================================================================
    // API — Relatos
    // =========================================================================

    public function showDadosGerais(string $ocorrenciaId): JsonResponse
    {
        $dadosGerais = RatRelatoDadosGerais::where('ocorrencia_id', $ocorrenciaId)->first();

        return response()->json(['success' => true, 'data' => $dadosGerais]);
    }

    public function storeDadosGerais(RatDadosGeraisRequest $request, string $ocorrenciaId): JsonResponse
    {
        $dadosGerais = $this->writeService->saveDadosGerais(
            $ocorrenciaId,
            RatDadosGeraisDTO::fromArray($request->validated())
        );

        return response()->json(['success' => true, 'message' => 'Dados gerais salvos.', 'data' => $dadosGerais], 201);
    }

    public function updateDadosGerais(RatDadosGeraisRequest $request, string $ocorrenciaId, string $id): JsonResponse
    {
        return $this->storeDadosGerais($request, $ocorrenciaId);
    }

    public function indexEnvolvidos(string $ocorrenciaId): JsonResponse
    {
        $envolvidos = RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)->get();

        return response()->json(['success' => true, 'data' => $envolvidos, 'count' => $envolvidos->count()]);
    }

    public function storeEnvolvidos(RatEnvolvidoRequest $request, string $ocorrenciaId): JsonResponse
    {
        $envolvido = $this->writeService->saveEnvolvido(
            $ocorrenciaId,
            RatEnvolvidoDTO::fromArray($request->validated())
        );

        return response()->json(['success' => true, 'message' => 'Envolvido salvo.', 'data' => $envolvido], 201);
    }

    public function updateEnvolvidos(RatEnvolvidoRequest $request, string $ocorrenciaId, string $id): JsonResponse
    {
        $request->merge(['id' => $id]);
        return $this->storeEnvolvidos($request, $ocorrenciaId);
    }

    public function destroyEnvolvidos(string $ocorrenciaId, string $id): JsonResponse
    {
        RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Envolvido removido.']);
    }

    public function indexRecursos(string $ocorrenciaId): JsonResponse
    {
        $recursos = RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)->with('agentes')->get();

        return response()->json(['success' => true, 'data' => $recursos, 'count' => $recursos->count()]);
    }

    public function storeRecursos(RatRecursoRequest $request, string $ocorrenciaId): JsonResponse
    {
        $recurso = $this->writeService->saveRecurso(
            $ocorrenciaId,
            RatRecursoDTO::fromArray($request->validated())
        );

        return response()->json(['success' => true, 'message' => 'Recurso salvo.', 'data' => $recurso], 201);
    }

    public function updateRecursos(RatRecursoRequest $request, string $ocorrenciaId, string $id): JsonResponse
    {
        $request->merge(['id' => $id]);
        return $this->storeRecursos($request, $ocorrenciaId);
    }

    public function destroyRecursos(string $ocorrenciaId, string $id): JsonResponse
    {
        RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Recurso removido.']);
    }

    public function showVistoria(string $ocorrenciaId): JsonResponse
    {
        $vistoria = RatRelatoVistoria::where('ocorrencia_id', $ocorrenciaId)->first();

        return response()->json(['success' => true, 'data' => $vistoria]);
    }

    public function storeVistoria(RatVistoriaRequest $request, string $ocorrenciaId): JsonResponse
    {
        // Usamos all() para que RatVistoriaDTO receba tanto campos v_* flat quanto estrutura aninhada
        $vistoria = $this->writeService->saveVistoria(
            $ocorrenciaId,
            RatVistoriaDTO::fromArray($request->all())
        );

        return response()->json(['success' => true, 'message' => 'Vistoria salva.', 'data' => $vistoria], 201);
    }

    public function updateVistoria(RatVistoriaRequest $request, string $ocorrenciaId, string $id): JsonResponse
    {
        return $this->storeVistoria($request, $ocorrenciaId);
    }

    public function showHistorico(string $ocorrenciaId): JsonResponse
    {
        $historico = RatOcorrenciaHistorico::where('ocorrencia_id', $ocorrenciaId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $historico, 'count' => $historico->count()]);
    }

    public function storeHistorico(RatHistoricoRequest $request, string $ocorrenciaId): JsonResponse
    {
        $this->writeService->saveHistorico(
            $ocorrenciaId,
            RatHistoricoDTO::fromArray($request->validated())
        );

        return response()->json(['success' => true, 'message' => 'Histórico salvo.']);
    }

    // =========================================================================
    // API — Attachments
    // =========================================================================

    public function storeAttachment(Request $request, string $ocorrenciaId): JsonResponse
    {
        $request->validate([
            'arquivo' => [
                'required', 'file',
                'max:20480', // 20 MB
                'mimes:jpeg,jpg,png,gif,webp,bmp,pdf,doc,docx,xls,xlsx,txt,csv,odt,ods',
            ],
            'tipo'      => 'required|string|in:imagem,documento,video,audio',
            'descricao' => 'nullable|string|max:500',
        ]);

        $rat = $this->writeService->findById($ocorrenciaId);
        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        $attachment = $this->attachmentService->store($rat, $request->file('arquivo'), $request->input('tipo', 'documento'));

        // Retorna o objeto diretamente para o frontend substituir o entry temporário pelo real
        return response()->json([
            'id'            => $attachment->id,
            'nome_original' => $attachment->nome_original,
            'nome_arquivo'  => $attachment->nome_arquivo,
            'mime_type'     => $attachment->mime_type,
            'tamanho_bytes' => $attachment->tamanho_bytes,
            'url'           => $attachment->url,
            'categoria'     => $attachment->categoria instanceof \BackedEnum ? $attachment->categoria->value : $attachment->categoria,
            'descricao'     => $attachment->descricao,
            'created_at'    => $attachment->created_at?->toIso8601String(),
        ], 201);
    }

    public function destroyAttachment(string $ocorrenciaId, string $attachmentId): JsonResponse
    {
        $rat = $this->writeService->findById($ocorrenciaId);
        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        $this->attachmentService->destroy($rat, $attachmentId);

        return response()->json(['success' => true, 'message' => 'Arquivo removido.']);
    }

    // =========================================================================
    // API — Export / Statistics / JSON
    // =========================================================================

    public function export(Request $request): StreamedResponse
    {
        return $this->exportService->exportToCsv($request);
    }

    /**
     * Versao assincrona do export — recomendado para datasets grandes.
     * Retorna 202 + trace_id; cliente consulta /api/v1/traces/{id} e baixa
     * o CSV via /download quando completed.
     */
    public function exportAsync(Request $request): JsonResponse
    {
        return $this->dispatchAsyncJob(
            jobClass: ExportRatToCsvJob::class,
            type: 'export_rat_csv',
            args: [[
                'type' => $request->input('type'),
                'data_inicio' => $request->input('data_inicio'),
                'data_fim' => $request->input('data_fim'),
            ]],
            meta: ['filters' => $request->only(['type', 'data_inicio', 'data_fim'])],
            queue: 'low',
            estimatedSeconds: 90,
        );
    }

    public function exportRats(Request $request): StreamedResponse
    {
        $filename = 'rat-ocorrencias-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['ID', 'Nº BOS', 'Status', 'Prazo Edição', 'Criado em'], ';');

            RatOcorrencia::orderByDesc('created_at')->each(function ($oc) use ($handle) {
                fputcsv($handle, [
                    $oc->id,
                    $oc->numero_bos,
                    $oc->status === 0 ? 'Rascunho' : 'Finalizado',
                    $oc->prazo_edicao?->format('d/m/Y H:i'),
                    $oc->created_at?->format('d/m/Y H:i'),
                ], ';');
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'total'    => RatOcorrencia::count(),
                'hoje'     => RatOcorrencia::whereDate('created_at', today())->count(),
                'esteMes'  => RatOcorrencia::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'esteAno'  => RatOcorrencia::whereYear('created_at', now()->year)->count(),
            ],
        ]);
    }

    public function showJson(string $id): JsonResponse
    {
        $rat = $this->writeService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        return response()->json((new RatOcorrenciaResource($rat))->resolve());
    }

    // =========================================================================
    // API V1 Mobile
    // =========================================================================

    public function v1Timeline(int $id, Request $request): JsonResponse
    {
        return response()->json(
            $this->historicoService->timeline($id, $request->integer('por_pagina', 20))
        );
    }

    public function v1Recent(int $id, Request $request): JsonResponse
    {
        return response()->json(
            $this->historicoService->recent($id, $request->integer('limite', 10))
        );
    }

    public function v1Store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'prazo_edicao' => 'nullable|date',
            'status'       => 'nullable|integer',
        ]);

        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(RatBoDTO::fromArray($validated));

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        $this->historicoService->log($ocorrencia, 'ocorrencia.registrada_mobile');

        return (new RatOcorrenciaResource($ocorrencia->load('relatosMorph')))
            ->response()
            ->setStatusCode(201);
    }

    public function protocolProxyIndex(): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => ['current_page' => 1, 'total' => 0],
        ]);
    }

    // =========================================================================
    // API — Criar Boletim Relacionado
    // =========================================================================

    public function createRelacionado(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);

        $isFinalized = $ocorrencia->status === 1;
        $isExpired   = $ocorrencia->prazo_edicao && $ocorrencia->prazo_edicao->isPast();

        if (!$isFinalized && !$isExpired) {
            $msg = 'Apenas boletins finalizados ou com prazo expirado podem ser relacionados.';
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['error' => $msg]);
        }

        $novo = $this->writeService->createRelacionado($id);

        $url = $this->boUrl($novo->id);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => "Boletim {$novo->numero_bos} criado com sucesso.",
                'id'         => $novo->id,
                'numero_bos' => $novo->numero_bos,
                'url'        => $url,
            ]);
        }

        return redirect()->to($url)->with('success', "Boletim {$novo->numero_bos} criado com sucesso!");
    }
}
