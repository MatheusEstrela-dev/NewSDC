<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Modules\Rat\Application\Services\RatService;
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
use App\Modules\Rat\Http\Resources\RatListResource;
use App\Modules\Rat\Http\Resources\RatResource as RatOcorrenciaResource;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\RatOcorrenciaHistorico;
use App\Modules\Rat\Models\RatOcorrenciaRelato;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatHistoricoService;
use App\Modules\Rat\Services\RatOcorrenciaService;
use App\Modules\Rat\Services\RatRelatoService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatUnifiedController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct(
        private readonly RatWriteService      $writeService,
        private readonly RatAttachmentService $attachmentService,
        private readonly RatExportService     $exportService,
        private readonly RatStatisticsService $statisticsService,
        private readonly RatService           $appDataService,
        private readonly RatOcorrenciaService $ocorrenciaService,
        private readonly RatRelatoService     $relatoService,
        private readonly RatHistoricoService  $historicoService,
    ) {}

    // =========================================================================
    // WEB — Inertia
    // =========================================================================

    public function index(Request $request): Response
    {
        $data = $this->appDataService->getIndexData();

        return Inertia::render('RatIndex', [
            'rats'       => RatListResource::collection($data['rats']),
            'statistics' => $data['statistics'],
            'filters'    => [],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Rat');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'dadosGerais' => 'nullable|array',
                'comunicacao' => 'nullable|array',
                'local'       => 'nullable|array',
                'endereco'    => 'nullable|array',
                'recursos'    => 'nullable|array',
                'envolvidos'  => 'nullable|array',
                'vistoria'    => 'nullable|array',
                'historico'   => 'nullable|array',
                'finalize'    => 'nullable|boolean',
            ]);

            $ocorrencia = $this->appDataService->createWithData($validated);

            return redirect()
                ->route('rat.edit', $ocorrencia->id)
                ->with('success', 'Ocorrência RAT criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar RAT: ' . $e->getMessage(), ['exception' => $e]);

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
            $historico = $request->input('historico', []);
            RatOcorrencia::where('id', $id)->update([
                'historico' => is_array($historico) ? $historico : [],
            ]);
        }

        RatOcorrencia::where('id', $id)->update(['updated_by' => auth()->id()]);

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

    public function draft(Request $request, string $id): RedirectResponse
    {
        $this->writeService->saveDraft($id, $request->all());

        return redirect()
            ->route('rat.show', $id)
            ->with('success', 'Rascunho salvo com sucesso!');
    }

    // =========================================================================
    // API — Boletim de Ocorrência
    // =========================================================================

    public function indexBo(Request $request): Response
    {
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
        $vistoria = $this->writeService->saveVistoria(
            $ocorrenciaId,
            RatVistoriaDTO::fromArray($request->validated())
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
            'arquivo'   => 'required|file|max:10240',
            'tipo'      => 'required|string|in:imagem,documento,video,audio',
            'descricao' => 'nullable|string|max:500',
        ]);

        $rat = $this->appDataService->findById($ocorrenciaId);
        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        $attachment = $this->attachmentService->store($rat, $request->file('arquivo'));

        return response()->json(['success' => true, 'message' => 'Arquivo enviado.', 'data' => $attachment], 201);
    }

    public function destroyAttachment(string $ocorrenciaId, string $attachmentId): JsonResponse
    {
        $rat = $this->appDataService->findById($ocorrenciaId);
        abort_if(!$rat, 404, 'Ocorrência não encontrada.');

        $this->attachmentService->destroy($rat, $attachmentId);

        return response()->json(['success' => true, 'message' => 'Arquivo removido.']);
    }

    // =========================================================================
    // API — Export / Statistics / JSON
    // =========================================================================

    public function export(Request $request): StreamedResponse
    {
        return $this->exportService->exportToCsv();
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
            'data'    => $this->statisticsService->getStatistics()->toArray(),
        ]);
    }

    public function showJson(string $id): JsonResponse
    {
        $rat = $this->appDataService->findById($id);
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
}
