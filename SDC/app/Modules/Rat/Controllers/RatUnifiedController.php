<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

// ─── DTOs ────────────────────────────────────────────────────────────────────
use App\Modules\Rat\DTOs\RatBoDTO;
use App\Modules\Rat\DTOs\RatOcorrenciaFiltroDTO;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\DTOs\RatHistoricoDTO;
use App\Modules\Rat\DTOs\RatFilterDTO;

// ─── Models ──────────────────────────────────────────────────────────────────
use App\Models\Rat\RatOcorrenciaHistorico;
use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;

// ─── Services (Modules layer) ─────────────────────────────────────────────────
use App\Modules\Rat\Services\RatWriteService;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Application\Services\RatService;

// ─── Services (App layer) ────────────────────────────────────────────────────
use App\Services\Rat\RatOcorrenciaService;
use App\Services\Rat\RatRelatoService;
use App\Services\Rat\RatAuditService;
use App\Services\Rat\RatNovoService;
use App\Services\Rat\RatHistoricoService;

// ─── HTTP ────────────────────────────────────────────────────────────────────
use App\Modules\Rat\Http\Requests\UpdateRatRequest;
use App\Modules\Rat\Http\Requests\ListRatRequest;
use App\Modules\Rat\Http\Resources\RatResource;

// ─── Laravel ─────────────────────────────────────────────────────────────────
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatUnifiedController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct(
        // Módulo interno
        private readonly RatWriteService      $writeService,
        private readonly RatAttachmentService $attachmentService,
        private readonly RatExportService     $exportService,
        private readonly RatStatisticsService $statisticsService,
        private readonly RatService           $appDataService,
        // Camada de aplicação
        private readonly RatOcorrenciaService $ocorrenciaService,
        private readonly RatRelatoService     $relatoService,
        private readonly RatAuditService      $auditService,
        private readonly RatNovoService       $novoService,
        private readonly RatHistoricoService  $historicoService,
    ) {}

    /**
     * Lista paginada de ocorrências RAT com filtros opcionais.
     * GET /compdec/rat
     */
    public function index(Request $request): Response
    {
        $filtro      = RatOcorrenciaFiltroDTO::fromArray($request->only(['status', 'numero_bos']));
        $ocorrencias = $this->ocorrenciaService->paginate($filtro);

        return Inertia::render('Compdec/Rat/Index', [
            'ocorrencias' => $ocorrencias,
            'filters'     => $request->only(['status', 'numero_bos']),
        ]);
    }

    /**
     * Formulário de criação de nova ocorrência.
     * GET /compdec/rat/create
     */
    public function create(): Response
    {
        return Inertia::render('Rat');
    }

    /**
     * Persiste nova ocorrência e redireciona para visualização.
     * POST /compdec/rat/store
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'prazo_edicao' => 'nullable|date',
            'status'       => 'nullable|integer',
        ]);

        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(
            RatBoDTO::fromArray($validated)
        );

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        return redirect()
            ->route('compdec.rat.show', $ocorrencia->id)
            ->with('success', 'Ocorrência RAT criada com sucesso!');
    }

    /**
     * Exibe detalhes de uma ocorrência (somente leitura).
     * GET /compdec/rat/{ocorrencia}
     */
    public function show(int $id): Response
    {
        $ocorrencia = $this->appDataService->findById((string)$id);

        abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

        return Inertia::render('Rat', [
            'rat'      => $ocorrencia,
            'viewOnly' => true,
        ]);
    }

    /**
     * Formulário de edição de uma ocorrência.
     * GET /compdec/rat/{ocorrencia}/edit
     */
    public function edit(int $id): Response
    {
        $ocorrencia = $this->appDataService->findById((string)$id);

        abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

        return Inertia::render('Rat', [
            'rat'      => $ocorrencia,
            'viewOnly' => false,
        ]);
    }

    /**
     * Atualiza os dados de uma ocorrência existente.
     * PUT /compdec/rat/{ocorrencia}
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'prazo_edicao' => 'nullable|date',
            'status'       => 'nullable|integer',
        ]);

        $this->ocorrenciaService->manageOcorrencia(
            RatBoDTO::fromArray($validated),
            $id
        );

        return redirect()
            ->route('compdec.rat.show', $id)
            ->with('success', 'Ocorrência atualizada com sucesso!');
    }

    /**
     * Exclui (soft delete) uma ocorrência.
     * DELETE /compdec/rat/{ocorrencia}
     */
    public function destroy(int $id): RedirectResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail($id);
        $ocorrencia->delete();

        return redirect()
            ->route('compdec.rat.index')
            ->with('success', 'Ocorrência ocultada com sucesso!');
    }

    /**
     * Finaliza a ocorrência — muda status para Finalizado.
     * PATCH /compdec/rat/{ocorrencia}/finalize
     */
    public function finalize(int $id): RedirectResponse
    {
        $this->ocorrenciaService->finalizar($id);

        return redirect()
            ->route('compdec.rat.show', $id)
            ->with('success', 'Ocorrência finalizada com sucesso!');
    }

    /**
     * Salva rascunho via Modules WriteService (fluxo alternativo).
     * POST /compdec/rat/{ocorrencia}/draft
     */
    public function draft(UpdateRatRequest $request, int $id): RedirectResponse
    {
        $this->writeService->saveDraft((string)$id, $request->validated());

        return redirect()
            ->route('compdec.rat.edit', $id)
            ->with('success', 'Rascunho salvo com sucesso!');
    }

    /**
     * Lista Boletins de Ocorrência paginados com filtros.
     * GET /compdec/rat/bo
     */
    public function indexBo(Request $request): Response
    {
        $filters = $request->validate([
            'status'      => 'nullable|string',
            'numero_bos'  => 'nullable|string',
            'data_inicio' => 'nullable|date',
            'data_fim'    => 'nullable|date',
        ]);

        $bos = RatRelatoDadosGerais::query()
            ->when($filters['status'] ?? null,      fn ($q, $v) => $q->where('status', $v))
            ->when($filters['numero_bos'] ?? null,  fn ($q, $v) => $q->whereHas('ocorrencia', fn ($s) => $s->where('numero_bos', 'like', "%{$v}%")))
            ->when($filters['data_inicio'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['data_fim'] ?? null,    fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->with(['ocorrencia', 'envolvidos', 'recursos', 'vistoria'])
            ->paginate(15)
            ->appends($filters);

        return Inertia::render('Rat/BoIndex', [
            'bos'     => $bos,
            'filters' => $filters,
        ]);
    }

    /**
     * Registra novo Boletim de Ocorrência via Service.
     * POST /compdec/rat/bo
     */
    public function storeBo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'prazo_edicao' => 'nullable|date',
            'status'       => 'nullable|integer',
        ]);

        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(
            RatBoDTO::fromArray($validated)
        );

        return response()->json([
            'success' => true,
            'message' => "BO {$ocorrencia->numero_bos} registrado com sucesso!",
            'data'    => $ocorrencia,
        ], 201);
    }

    /**
     * Exibe detalhes de um BO específico.
     * GET /compdec/rat/bo/{id}  (legacy alias para showBo)
     */
    public function showBo(int $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $ocorrencia,
        ]);
    }

    /**
     * Retorna os dados gerais de uma ocorrência.
     * GET /compdec/rat/ocorrencias/{ocorrencia}/dados-gerais
     */
    public function showDadosGerais(int $ocorrenciaId): JsonResponse
    {
        $dadosGerais = RatRelatoDadosGerais::where('ocorrencia_id', $ocorrenciaId)->first();

        return response()->json([
            'success' => true,
            'data'    => $dadosGerais,
        ]);
    }

    /**
     * Cria ou atualiza os dados gerais de uma ocorrência.
     * POST /compdec/rat/ocorrencias/{ocorrencia}/dados-gerais
     */
    public function storeDadosGerais(Request $request, int $ocorrenciaId): JsonResponse
    {
        $dto         = RatDadosGeraisDTO::fromArray($request->all());
        $dadosGerais = $this->writeService->saveDadosGerais((string) $ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Dados gerais salvos com sucesso.',
            'data'    => $dadosGerais,
        ], 201);
    }

    /**
     * Atualiza dados gerais (alias polimórfico para storeDadosGerais).
     * PUT /compdec/rat/ocorrencias/{ocorrencia}/dados-gerais/{id}
     */
    public function updateDadosGerais(Request $request, int $ocorrenciaId, int $id): JsonResponse
    {
        return $this->storeDadosGerais($request, $ocorrenciaId);
    }

    /**
     * Lista todos os envolvidos de uma ocorrência.
     * GET /compdec/rat/ocorrencias/{ocorrencia}/envolvidos
     */
    public function indexEnvolvidos(int $ocorrenciaId): JsonResponse
    {
        $envolvidos = RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)->get();

        return response()->json([
            'success' => true,
            'data'    => $envolvidos,
            'count'   => $envolvidos->count(),
        ]);
    }

    /**
     * Adiciona ou atualiza um envolvido na ocorrência.
     * POST /compdec/rat/ocorrencias/{ocorrencia}/envolvidos
     */
    public function storeEnvolvidos(Request $request, int $ocorrenciaId): JsonResponse
    {
        $dto       = RatEnvolvidoDTO::fromArray($request->all());
        $envolvido = $this->writeService->saveEnvolvido((string) $ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Envolvido salvo com sucesso.',
            'data'    => $envolvido,
        ], 201);
    }

    /**
     * Atualiza um envolvido existente.
     * PUT /compdec/rat/ocorrencias/{ocorrencia}/envolvidos/{id}
     */
    public function updateEnvolvidos(Request $request, int $ocorrenciaId, int $id): JsonResponse
    {
        // Injeta o ID no payload para que o DTO passe o id ao updateOrCreate.
        $request->merge(['id' => $id]);
        return $this->storeEnvolvidos($request, $ocorrenciaId);
    }

    /**
     * Remove um envolvido da ocorrência.
     * DELETE /compdec/rat/ocorrencias/{ocorrencia}/envolvidos/{id}
     */
    public function destroyEnvolvidos(int $ocorrenciaId, int $id): JsonResponse
    {
        RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)->findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Envolvido removido com sucesso.',
        ]);
    }

    /**
     * Lista os recursos empregados de uma ocorrência.
     * GET /compdec/rat/ocorrencias/{ocorrencia}/recursos
     */
    public function indexRecursos(int $ocorrenciaId): JsonResponse
    {
        $recursos = RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)
            ->with('operacionais', 'agentes')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $recursos,
            'count'   => $recursos->count(),
        ]);
    }

    /**
     * Adiciona ou atualiza um recurso empregado.
     * POST /compdec/rat/ocorrencias/{ocorrencia}/recursos
     */
    public function storeRecursos(Request $request, int $ocorrenciaId): JsonResponse
    {
        $dto     = RatRecursoDTO::fromArray($request->all());
        $recurso = $this->writeService->saveRecurso((string) $ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Recurso salvo com sucesso.',
            'data'    => $recurso,
        ], 201);
    }

    /**
     * Atualiza um recurso existente.
     * PUT /compdec/rat/ocorrencias/{ocorrencia}/recursos/{id}
     */
    public function updateRecursos(Request $request, int $ocorrenciaId, int $id): JsonResponse
    {
        $request->merge(['id' => $id]);
        return $this->storeRecursos($request, $ocorrenciaId);
    }

    /**
     * Remove um recurso da ocorrência.
     * DELETE /compdec/rat/ocorrencias/{ocorrencia}/recursos/{id}
     */
    public function destroyRecursos(int $ocorrenciaId, int $id): JsonResponse
    {
        RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)->findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recurso removido com sucesso.',
        ]);
    }

    /**
     * Retorna a vistoria técnica de uma ocorrência.
     * GET /compdec/rat/ocorrencias/{ocorrencia}/vistoria
     */
    public function showVistoria(int $ocorrenciaId): JsonResponse
    {
        $vistoria = RatRelatoVistoria::where('ocorrencia_id', $ocorrenciaId)->first();

        return response()->json([
            'success' => true,
            'data'    => $vistoria,
        ]);
    }

    /**
     * Cria ou atualiza a vistoria de uma ocorrência.
     * POST /compdec/rat/ocorrencias/{ocorrencia}/vistoria
     */
    public function storeVistoria(Request $request, int $ocorrenciaId): JsonResponse
    {
        $dto      = RatVistoriaDTO::fromArray($request->all());
        $vistoria = $this->writeService->saveVistoria((string) $ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Vistoria salva com sucesso.',
            'data'    => $vistoria,
        ], 201);
    }

    /**
     * Atualiza a vistoria (alias para storeVistoria no modelo upsert).
     * PUT /compdec/rat/ocorrencias/{ocorrencia}/vistoria/{id}
     */
    public function updateVistoria(Request $request, int $ocorrenciaId, int $id): JsonResponse
    {
        return $this->storeVistoria($request, $ocorrenciaId);
    }

    /**
     * Retorna o histórico de eventos de uma ocorrência.
     * GET /compdec/rat/ocorrencias/{ocorrencia}/historico
     */
    public function showHistorico(int $ocorrenciaId): JsonResponse
    {
        $historico = RatOcorrenciaHistorico::where('ocorrencia_id', $ocorrenciaId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $historico,
            'count'   => $historico->count(),
        ]);
    }

    /**
     * Registra um evento no histórico da ocorrência.
     * POST /compdec/rat/ocorrencias/{ocorrencia}/historico
     */
    public function storeHistorico(Request $request, int $ocorrenciaId): JsonResponse
    {
        $dto = RatHistoricoDTO::fromArray($request->all());
        $this->writeService->saveHistorico((string) $ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Histórico salvo com sucesso.',
        ]);
    }

    /**
     * Faz upload de um arquivo e vincula à ocorrência.
     * POST /compdec/rat/ocorrencias/{ocorrencia}/attachments
     */
    public function storeAttachment(Request $request, int $ocorrenciaId): JsonResponse
    {
        $request->validate([
            'arquivo'   => 'required|file|max:10240',
            'tipo'      => 'required|string|in:imagem,documento,video,audio',
            'descricao' => 'nullable|string|max:500',
        ]);

        $rat = $this->appDataService->findById((string) $ocorrenciaId);

        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        $attachment = $this->attachmentService->store($rat, $request->file('arquivo'));

        return response()->json([
            'success' => true,
            'message' => 'Arquivo enviado com sucesso.',
            'data'    => $attachment,
        ], 201);
    }

    /**
     * Remove um arquivo da ocorrência.
     * DELETE /compdec/rat/ocorrencias/{ocorrencia}/attachments/{attachmentId}
     */
    public function destroyAttachment(int $ocorrenciaId, string $attachmentId): JsonResponse
    {
        $rat = $this->appDataService->findById((string) $ocorrenciaId);
        $this->attachmentService->destroy($rat, $attachmentId);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo removido com sucesso.',
        ]);
    }

    /**
     * Lista logs de auditoria globais do módulo RAT.
     * GET /compdec/rat/audit
     */
    public function auditIndex(Request $request): JsonResponse
    {
        $history = $this->auditService->history(
            'rat_ocorrencias',
            0,
            $request->integer('per_page', 20)
        );

        return response()->json($history);
    }

    /**
     * Histórico de auditoria de uma ocorrência específica.
     * GET /compdec/rat/audit/{id}
     */
    public function auditShow(int $id, Request $request): JsonResponse
    {
        $history = $this->auditService->history(
            'rat_ocorrencias',
            $id,
            $request->integer('per_page', 20)
        );

        return response()->json($history);
    }

    /**
     * Exporta RATs em formato solicitado (pdf, excel, csv).
     * GET /compdec/rat/export
     */
    /**
     * Exporta RATs em formato CSV.
     * GET /compdec/rat/export
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = RatFilterDTO::fromArray($request->query('filters', []));
        return $this->exportService->exportToCsv($filters);
    }

    /**
     * Exporta ocorrências em CSV com BOM UTF-8.
     * GET /compdec/rat/export-rats
     */
    public function exportRats(Request $request): StreamedResponse
    {
        $filtro   = RatOcorrenciaFiltroDTO::fromArray($request->only(['status', 'numero_bos']));
        $filename = 'rat-ocorrencias-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($filtro) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, ['ID', 'Nº BOS', 'Status', 'Prazo Edição', 'Criado em'], ';');

            $this->ocorrenciaService->paginate($filtro)->each(function ($oc) use ($handle) {
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

    /**
     * Retorna estatísticas gerais do módulo RAT.
     * GET /compdec/rat/statistics
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->statisticsService->getStatistics()->toArray(),
        ]);
    }

    /**
     * Retorna dados normalizados de uma ocorrência para PowerBI.
     * GET /compdec/rat/{id}/normalized
     */
    public function normalizedData(int $id, Request $request): JsonResponse
    {
        $request->merge(['ocorrencia_id' => $id]);
        return response()->json($this->novoService->getNormalizedDataForPowerBI($request));
    }

    /**
     * Payload estruturado para dashboard Power BI.
     * GET /compdec/rat/{id}/power-bi
     */
    public function powerBiData(int $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail($id);

        return response()->json([
            'dados_gerais' => $this->novoService->extractDadosGerais($ocorrencia),
            'envolvidos'   => $this->novoService->extractEnvolvidos($ocorrencia),
            'recursos'     => $this->novoService->extractRecursos($ocorrencia),
        ]);
    }

    /**
     * Retorna dados do RAT em JSON puro para modal de impressão.
     * GET /compdec/rat/{id}/json
     */
    public function showJson(string $id): JsonResponse
    {
        $rat = $this->appDataService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        return response()->json((new RatResource($rat))->resolve());
    }

    /**
     * Timeline de eventos — API V1 Mobile.
     * GET /compdec/rat/v1/ocorrencias/{id}/historico
     */
    public function v1Timeline(int $id, Request $request): JsonResponse
    {
        $porPagina = $request->integer('por_pagina', 20);

        return response()->json($this->historicoService->timeline($id, $porPagina));
    }

    /**
     * Eventos recentes para widgets e cards — API V1 Mobile.
     * GET /compdec/rat/v1/ocorrencias/{id}/historico/recent
     */
    public function v1Recent(int $id, Request $request): JsonResponse
    {
        $limite = $request->integer('limite', 10);

        return response()->json($this->historicoService->recent($id, $limite));
    }

    /**
     * Criação de ocorrência via API Mobile com registro no histórico.
     * POST /compdec/rat/v1/ocorrencias
     */
    public function v1Store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'prazo_edicao' => 'nullable|date',
            'status'       => 'nullable|integer',
        ]);

        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(
            RatBoDTO::fromArray($validated)
        );

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        $this->historicoService->log($ocorrencia, 'ocorrencia.registrada_mobile');

        return (new RatResource($ocorrencia->load('relatosMorph')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Proxy de protocolos (compatibilidade com ProtocoloController legado).
     * GET /compdec/rat/v1/protocolos
     */
    public function protocolProxyIndex(): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => ['current_page' => 1, 'total' => 0],
        ]);
    }
}
