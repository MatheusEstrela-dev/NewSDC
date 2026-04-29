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
use App\Modules\Rat\Http\Resources\RatResource as RatOcorrenciaResource;
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Infrastructure\Services\RatAppDataService;
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
use App\Modules\Rat\Http\Requests\RatDadosGeraisRequest;
use App\Modules\Rat\Http\Requests\RatEnvolvidoRequest;
use App\Modules\Rat\Http\Requests\RatRecursoRequest;
use App\Modules\Rat\Http\Requests\RatVistoriaRequest;
use App\Modules\Rat\Http\Requests\RatHistoricoRequest;

// ─── Laravel ─────────────────────────────────────────────────────────────────
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
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

    public function index(Request $request): Response
    {
        $filtersDTO = RatFilterDTO::fromArray($request->all());
        $data       = $this->appDataService->getIndexData($filtersDTO);

        return Inertia::render('RatIndex', [
            'rats'           => RatListResource::collection($data['rats']),
            'statistics'     => $data['statistics'],
            'filters'        => $request->all(),
            'municipalities' => $data['municipalities'] ?? [],
            'cobradeTypes'   => [
                ['id' => '1', 'name' => 'Natural'],
                ['id' => '2', 'name' => 'Tecnológico'],
            ],
            'years' => collect(range(date('Y'), date('Y') - 5))->map(fn($y) => ['id' => $y, 'name' => (string)$y])->toArray(),
        ]);
    }

    /**
     * Formulário de criação de nova ocorrência.
     * GET /rat/create
     */
    public function create(): Response
    {
        return Inertia::render('Rat');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        try {
            // Validação básica dos dados obrigatórios
            $validated = $request->validate([
                'dadosGerais' => 'nullable|array',
                'comunicacao' => 'nullable|array',
                'local' => 'nullable|array',
                'endereco' => 'nullable|array',
                'recursos' => 'nullable|array',
                'envolvidos' => 'nullable|array',
                'vistoria' => 'nullable|array',
                'historico' => 'nullable|array',
                'finalize' => 'nullable|boolean',
            ]);

            $ocorrencia = $this->appDataService->createWithData($validated);

            return redirect()
                ->route('rat.edit', $ocorrencia->id)
                ->with('success', 'Ocorrência RAT criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar RAT: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            // Se for requisição AJAX, retorna JSON
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar RAT: ' . $e->getMessage(),
                    'debug' => config('app.debug') ? $e->getTraceAsString() : null,
                ], 500);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao criar RAT: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(string $id): Response
    {
        $ocorrencia = $this->appDataService->findById($id);

        abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

        return Inertia::render('Rat', [
            'rat'      => new RatOcorrenciaResource($ocorrencia),
            'viewOnly' => true,
        ]);
    }

    public function edit(string $id): Response
    {
        $ocorrencia = $this->appDataService->findById($id);

        abort_if(!$ocorrencia, 404, 'Ocorrência não encontrada.');

        return Inertia::render('Rat', [
            'rat'      => new RatOcorrenciaResource($ocorrencia),
            'viewOnly' => false,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        // Dados Gerais
        if ($request->has('dadosGerais') || $request->has('comunicacao') || $request->has('local') || $request->has('endereco')) {
            $this->writeService->saveDadosGerais($id, RatDadosGeraisDTO::fromArray($request->all()));
        }

        // Recursos — sincroniza: apaga todos os existentes e recria
        if ($request->has('recursos')) {
            $incoming = $request->input('recursos', []);

            // Apaga os registros existentes diretamente
            \App\Modules\Rat\Models\Relatos\RatRelatoRecurso::where('ocorrencia_id', $id)->delete();
            \App\Models\Rat\RatOcorrenciaRelato::where('ocorrencia_id', $id)
                ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoRecurso::class)
                ->forceDelete();

            foreach ($incoming as $index => $recursoData) {
                unset($recursoData['id']);
                $recursoData['seq'] = $recursoData['seq'] ?? ($index + 1);
                $this->writeService->saveRecurso($id, RatRecursoDTO::fromArray($recursoData));
            }
        }

        // Envolvidos — sincroniza: apaga todos os existentes e recria
        if ($request->has('envolvidos')) {
            $incoming = $request->input('envolvidos', []);

            \App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos::where('ocorrencia_id', $id)->delete();
            \App\Models\Rat\RatOcorrenciaRelato::where('ocorrencia_id', $id)
                ->where('conteudo_type', \App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos::class)
                ->forceDelete();

            foreach ($incoming as $index => $envolvidoData) {
                unset($envolvidoData['id']);
                $envolvidoData['seq'] = $envolvidoData['seq'] ?? ($index + 1);
                $this->writeService->saveEnvolvido($id, RatEnvolvidoDTO::fromArray($envolvidoData));
            }
        }

        // Vistoria
        if ($request->has('vistoria') && !empty($request->input('vistoria'))) {
            $this->writeService->saveVistoria($id, RatVistoriaDTO::fromArray($request->input('vistoria')));
        }

        // Histórico — salva diretamente como JSON
        if ($request->has('historico')) {
            $historico = $request->input('historico', []);
            \App\Models\Rat\RatOcorrencia::where('id', $id)->update([
                'historico' => is_array($historico) ? $historico : [],
            ]);
        }

        return redirect()
            ->route('rat.edit', $id)
            ->with('success', 'Ocorrência atualizada com sucesso!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->appDataService->delete($id);

        return redirect()
            ->route('rat.index')
            ->with('success', 'Ocorrência excluída com sucesso!');
    }

    public function finalize(string $id): RedirectResponse
    {
        $this->appDataService->finalize($id);

        return redirect()
            ->route('rat.show', $id)
            ->with('success', 'Ocorrência finalizada com sucesso!');
    }

    /**
     * Salva rascunho via Modules WriteService.
     * POST /rat/{ocorrencia}/draft
     */
    public function draft(Request $request, string $id): RedirectResponse
    {
        $this->writeService->saveDraft($id, $request->all());

        return redirect()
            ->route('rat.show', $id)
            ->with('success', 'Rascunho salvo com sucesso!');
    }

    /**
     * Lista Boletins de Ocorrência paginados com filtros.
     * GET /rat/bo
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
     * POST /rat/bo
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
     * GET /rat/bo/{id}  (legacy alias para showBo)
     */
    public function showBo(string $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail((int) $id);

        return response()->json([
            'success' => true,
            'data'    => $ocorrencia,
        ]);
    }

    /**
     * Retorna os dados gerais de uma ocorrência.
     * GET /rat/ocorrencias/{ocorrencia}/dados-gerais
     */
    public function showDadosGerais(string $ocorrenciaId): JsonResponse
    {
        $dadosGerais = RatRelatoDadosGerais::where('ocorrencia_id', $ocorrenciaId)->first();

        return response()->json([
            'success' => true,
            'data'    => $dadosGerais,
        ]);
    }

    /**
     * Cria ou atualiza os dados gerais de uma ocorrência.
     * POST /rat/ocorrencias/{ocorrencia}/dados-gerais
     */
    public function storeDadosGerais(RatDadosGeraisRequest $request, string $ocorrenciaId): JsonResponse
    {
        $dto         = RatDadosGeraisDTO::fromArray($request->validated());
        $dadosGerais = $this->writeService->saveDadosGerais($ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Dados gerais salvos com sucesso.',
            'data'    => $dadosGerais,
        ], 201);
    }

    /**
     * Atualiza dados gerais (alias polimórfico para storeDadosGerais).
     * PUT /rat/ocorrencias/{ocorrencia}/dados-gerais/{id}
     */
    public function updateDadosGerais(Request $request, string $ocorrenciaId, string $id): JsonResponse
    {
        return $this->storeDadosGerais($request, $ocorrenciaId);
    }

    /**
     * Lista todos os envolvidos de uma ocorrência.
     * GET /rat/ocorrencias/{ocorrencia}/envolvidos
     */
    public function indexEnvolvidos(string $ocorrenciaId): JsonResponse
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
     * POST /rat/ocorrencias/{ocorrencia}/envolvidos
     */
    public function storeEnvolvidos(RatEnvolvidoRequest $request, string $ocorrenciaId): JsonResponse
    {
        $dto       = RatEnvolvidoDTO::fromArray($request->validated());
        $envolvido = $this->writeService->saveEnvolvido($ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Envolvido salvo com sucesso.',
            'data'    => $envolvido,
        ], 201);
    }

    /**
     * Atualiza um envolvido existente.
     * PUT /rat/ocorrencias/{ocorrencia}/envolvidos/{id}
     */
    public function updateEnvolvidos(Request $request, string $ocorrenciaId, string $id): JsonResponse
    {
        // Injeta o ID no payload para que o DTO passe o id ao updateOrCreate.
        $request->merge(['id' => $id]);
        return $this->storeEnvolvidos($request, $ocorrenciaId);
    }

    /**
     * Remove um envolvido da ocorrência.
     * DELETE /rat/ocorrencias/{ocorrencia}/envolvidos/{id}
     */
    public function destroyEnvolvidos(string $ocorrenciaId, string $id): JsonResponse
    {
        RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)->findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Envolvido removido com sucesso.',
        ]);
    }

    /**
     * Lista os recursos empregados de uma ocorrência.
     * GET /rat/ocorrencias/{ocorrencia}/recursos
     */
    public function indexRecursos(string $ocorrenciaId): JsonResponse
    {
        $recursos = RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)
            ->with('empregados', 'agentes')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $recursos,
            'count'   => $recursos->count(),
        ]);
    }

    /**
     * Adiciona ou atualiza um recurso empregado.
     * POST /rat/ocorrencias/{ocorrencia}/recursos
     */
    public function storeRecursos(RatRecursoRequest $request, string $ocorrenciaId): JsonResponse
    {
        $dto     = RatRecursoDTO::fromArray($request->validated());
        $recurso = $this->writeService->saveRecurso($ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Recurso salvo com sucesso.',
            'data'    => $recurso,
        ], 201);
    }

    /**
     * Atualiza um recurso existente.
     * PUT /rat/ocorrencias/{ocorrencia}/recursos/{id}
     */
    public function updateRecursos(Request $request, string $ocorrenciaId, string $id): JsonResponse
    {
        $request->merge(['id' => $id]);
        return $this->storeRecursos($request, $ocorrenciaId);
    }

    /**
     * Remove um recurso da ocorrência.
     * DELETE /rat/ocorrencias/{ocorrencia}/recursos/{id}
     */
    public function destroyRecursos(string $ocorrenciaId, string $id): JsonResponse
    {
        RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)->findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recurso removido com sucesso.',
        ]);
    }

    /**
     * Retorna a vistoria técnica de uma ocorrência.
     * GET /rat/ocorrencias/{ocorrencia}/vistoria
     */
    public function showVistoria(string $ocorrenciaId): JsonResponse
    {
        $vistoria = RatRelatoVistoria::where('ocorrencia_id', $ocorrenciaId)->first();

        return response()->json([
            'success' => true,
            'data'    => $vistoria,
        ]);
    }

    /**
     * Cria ou atualiza a vistoria de uma ocorrência.
     * POST /rat/ocorrencias/{ocorrencia}/vistoria
     */
    public function storeVistoria(RatVistoriaRequest $request, string $ocorrenciaId): JsonResponse
    {
        $dto      = RatVistoriaDTO::fromArray($request->validated());
        $vistoria = $this->writeService->saveVistoria($ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Vistoria salva com sucesso.',
            'data'    => $vistoria,
        ], 201);
    }

    /**
     * Atualiza a vistoria (alias para storeVistoria no modelo upsert).
     * PUT /rat/ocorrencias/{ocorrencia}/vistoria/{id}
     */
    public function updateVistoria(Request $request, string $ocorrenciaId, string $id): JsonResponse
    {
        return $this->storeVistoria($request, $ocorrenciaId);
    }

    /**
     * Retorna o histórico de eventos de uma ocorrência.
     * GET /rat/ocorrencias/{ocorrencia}/historico
     */
    public function showHistorico(string $ocorrenciaId): JsonResponse
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
     * POST /rat/ocorrencias/{ocorrencia}/historico
     */
    public function storeHistorico(RatHistoricoRequest $request, string $ocorrenciaId): JsonResponse
    {
        $dto = RatHistoricoDTO::fromArray($request->validated());
        $this->writeService->saveHistorico($ocorrenciaId, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Histórico salvo com sucesso.',
        ]);
    }

    /**
     * Faz upload de um arquivo e vincula à ocorrência.
     * POST /rat/ocorrencias/{ocorrencia}/attachments
     */
    public function storeAttachment(Request $request, string $ocorrenciaId): JsonResponse
    {
        $request->validate([
            'arquivo'   => 'required|file|max:10240',
            'tipo'      => 'required|string|in:imagem,documento,video,audio',
            'descricao' => 'nullable|string|max:500',
        ]);

        $rat = $this->appDataService->findById((string) $ocorrenciaId);

        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        /** @var \App\Models\Rat\RatOcorrencia $rat */
        $attachment = $this->attachmentService->store($rat, $request->file('arquivo'));

        return response()->json([
            'success' => true,
            'message' => 'Arquivo enviado com sucesso.',
            'data'    => $attachment,
        ], 201);
    }

    /**
     * Remove um arquivo da ocorrência.
     * DELETE /rat/ocorrencias/{ocorrencia}/attachments/{attachmentId}
     */
    public function destroyAttachment(string $ocorrenciaId, string $attachmentId): JsonResponse
    {
        $rat = $this->appDataService->findById($ocorrenciaId);

        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        /** @var \App\Models\Rat\RatOcorrencia $rat */
        $this->attachmentService->destroy($rat, $attachmentId);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo removido com sucesso.',
        ]);
    }

    /**
     * Lista logs de auditoria globais do módulo RAT.
     * GET /rat/audit
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
     * GET /rat/audit/{id}
     */
    public function auditShow(string $id, Request $request): JsonResponse
    {
        $history = $this->auditService->history(
            'rat_ocorrencias',
            (int) $id,
            $request->integer('per_page', 20)
        );

        return response()->json($history);
    }

    /**
     * Exporta RATs em formato solicitado (pdf, excel, csv).
     * GET /rat/export
     */
    /**
     * Exporta RATs em formato CSV.
     * GET /rat/export
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = RatFilterDTO::fromArray($request->query('filters', []));
        return $this->exportService->exportToCsv($filters);
    }

    /**
     * Exporta ocorrências em CSV com BOM UTF-8.
     * GET /rat/export-rats
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
     * GET /rat/statistics
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
     * GET /rat/{id}/normalized
     */
    public function normalizedData(string $id, Request $request): JsonResponse
    {
        $request->merge(['ocorrencia_id' => $id]);
        return response()->json($this->novoService->getNormalizedDataForPowerBI($request));
    }

    /**
     * Payload estruturado para dashboard Power BI.
     * GET /rat/{id}/power-bi
     */
    public function powerBiData(string $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail((int) $id);

        return response()->json([
            'dados_gerais' => $this->novoService->extractDadosGerais($ocorrencia),
            'envolvidos'   => $this->novoService->extractEnvolvidos($ocorrencia),
            'recursos'     => $this->novoService->extractRecursos($ocorrencia),
        ]);
    }

    /**
     * Retorna dados do RAT em JSON puro para modal de impressão.
     * GET /rat/{id}/json
     */
    public function showJson(string $id): JsonResponse
    {
        $rat = $this->appDataService->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');

        return response()->json((new RatOcorrenciaResource($rat))->resolve());
    }

    /**
     * Timeline de eventos — API V1 Mobile.
     * GET /rat/v1/ocorrencias/{id}/historico
     */
    public function v1Timeline(int $id, Request $request): JsonResponse
    {
        $porPagina = $request->integer('por_pagina', 20);

        return response()->json($this->historicoService->timeline($id, $porPagina));
    }

    /**
     * Eventos recentes para widgets e cards — API V1 Mobile.
     * GET /rat/v1/ocorrencias/{id}/historico/recent
     */
    public function v1Recent(int $id, Request $request): JsonResponse
    {
        $limite = $request->integer('limite', 10);

        return response()->json($this->historicoService->recent($id, $limite));
    }

    /**
     * Criação de ocorrência via API Mobile com registro no histórico.
     * POST /rat/v1/ocorrencias
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

        return (new RatOcorrenciaResource($ocorrencia->load('relatosMorph')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Proxy de protocolos (compatibilidade com ProtocoloController legado).
     * GET /rat/v1/protocolos
     */
    public function protocolProxyIndex(): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => ['current_page' => 1, 'total' => 0],
        ]);
    }
}
