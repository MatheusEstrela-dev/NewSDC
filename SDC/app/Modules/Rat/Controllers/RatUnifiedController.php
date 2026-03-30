<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatAgenteDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\DTOs\RatHistoricoDTO;
use App\Modules\Rat\Models\RatOcorrenciaHistorico;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use App\Modules\Rat\Services\RatWriteService;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatStatisticsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RatUnifiedController - Controller Unificado do módulo RAT
 *
 * Consolidação de todos os controllers RAT em uma única entidade.
 * Gerencia: Boletim de Ocorrência, Dados Gerais, Envolvidos, Recursos, Vistoria e Histórico.
 *
 * Métodos principais:
 * - Boletim de Ocorrência: indexBo, storeBo, showBo
 * - Dados Gerais: showDadosGerais, storeDadosGerais, updateDadosGerais
 * - Envolvidos: indexEnvolvidos, storeEnvolvidos, updateEnvolvidos, destroyEnvolvidos
 * - Recursos: indexRecursos, storeRecursos, updateRecursos, destroyRecursos
 * - Vistoria: showVistoria, storeVistoria, updateVistoria
 * - Histórico: showHistorico
 * - Arquivos: storeAttachment, destroyAttachment
 */
class RatUnifiedController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct(
        private readonly RatWriteService $writeService,
        private readonly RatAttachmentService $attachmentService,
        private readonly RatExportService $exportService,
        private readonly RatStatisticsService $statisticsService,
    ) {}

    // ========================================================================
    // BOLETIM DE OCORRÊNCIA
    // ========================================================================

    /**
     * Listagem de Boletins de Ocorrência
     * GET /api/rat/bo
     */
    public function indexBo(Request $request): Response
    {
        $filters = $request->validate([
            'status' => 'nullable|string',
            'numero_bos' => 'nullable|string',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
            'page' => 'nullable|integer',
        ]);

        $bos = RatRelatoDadosGerais::query()
            ->when($filters['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($filters['numero_bos'] ?? null, function ($query, $numero) {
                return $query->whereHas('ocorrencia', function ($q) use ($numero) {
                    $q->where('numero_bos', 'like', "%{$numero}%");
                });
            })
            ->when($filters['data_inicio'] ?? null, function ($query, $date) {
                return $query->whereDate('created_at', '>=', $date);
            })
            ->when($filters['data_fim'] ?? null, function ($query, $date) {
                return $query->whereDate('created_at', '<=', $date);
            })
            ->with(['ocorrencia', 'envolvidos', 'recursos', 'vistoria'])
            ->paginate(15)
            ->appends($filters);

        return Inertia::render('Rat/BoIndex', [
            'bos' => $bos,
            'filters' => $filters,
        ]);
    }

    /**
     * Armazenar novo Boletim de Ocorrência
     * POST /api/rat/bo
     */
    public function storeBo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos' => 'required|string|unique:rat_relato_dados_gerais',
            'data_ocorrencia' => 'required|date',
            'local_fato' => 'required|string',
            'categoria' => 'required|string',
            'orgao_responsavel' => 'required|string',
        ]);

        try {
            $bo = RatRelatoDadosGerais::create([
                'numero_bos' => $validated['numero_bos'],
                'data_ocorrencia' => $validated['data_ocorrencia'],
                'local_fato' => $validated['local_fato'],
                'categoria' => $validated['categoria'],
                'orgao_responsavel' => $validated['orgao_responsavel'],
                'status' => 'rascunho',
                'usuario_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Boletim criado com sucesso',
                'data' => $bo,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar boletim: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibir Boletim de Ocorrência
     * GET /api/rat/bo/{id}
     */
    public function showBo($id): JsonResponse
    {
        try {
            $bo = RatRelatoDadosGerais::with([
                'ocorrencia',
                'envolvidos',
                'recursos',
                'vistoria',
                'historico',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $bo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Boletim não encontrado',
            ], 404);
        }
    }

    // ========================================================================
    // DADOS GERAIS
    // ========================================================================

    /**
     * Exibir Dados Gerais
     * GET /api/rat/{ocorrencia}/dados-gerais
     */
    public function showDadosGerais($ocorrenciaId): JsonResponse
    {
        try {
            $dadosGerais = RatRelatoDadosGerais::where('ocorrencia_id', $ocorrenciaId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $dadosGerais,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados gerais não encontrados',
            ], 404);
        }
    }

    /**
     * Armazenar ou Atualizar Dados Gerais
     * POST /api/rat/{ocorrencia}/dados-gerais
     */
    public function storeDadosGerais(Request $request, $ocorrenciaId): JsonResponse
    {
        try {
            $dto = RatDadosGeraisDTO::fromArray($request->all());
            $dadosGerais = $this->writeService->saveDadosGerais((string) $ocorrenciaId, $dto);

            return response()->json([
                'success' => true,
                'message' => 'Dados gerais salvos com sucesso',
                'data' => $dadosGerais,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar dados gerais: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar Dados Gerais (Alias para storeDadosGerais no modelo polimórfico)
     * PUT /api/rat/{ocorrencia}/dados-gerais/{id}
     */
    public function updateDadosGerais(Request $request, $ocorrenciaId, $id): JsonResponse
    {
        return $this->storeDadosGerais($request, $ocorrenciaId);
    }

    // ========================================================================
    // ENVOLVIDOS
    // ========================================================================

    /**
     * Listagem de Envolvidos
     * GET /api/rat/{ocorrencia}/envolvidos
     */
    public function indexEnvolvidos($ocorrenciaId): JsonResponse
    {
        try {
            $envolvidos = RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)
                ->with('pessoa')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $envolvidos,
                'count' => $envolvidos->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar envolvidos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Armazenar ou Atualizar Envolvido
     * POST /api/rat/{ocorrencia}/envolvidos
     */
    public function storeEnvolvidos(Request $request, $ocorrenciaId): JsonResponse
    {
        try {
            $dto = RatEnvolvidoDTO::fromArray($request->all());
            $envolvido = $this->writeService->saveEnvolvido((string) $ocorrenciaId, $dto);

            return response()->json([
                'success' => true,
                'message' => 'Envolvido salvo com sucesso',
                'data' => $envolvido,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar envolvido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar Envolvido
     * PUT /api/rat/{ocorrencia}/envolvidos/{id}
     */
    public function updateEnvolvidos(Request $request, $ocorrenciaId, $id): JsonResponse
    {
        $data = $request->all();
        $data['id'] = $id;
        return $this->storeEnvolvidos(new Request($data), $ocorrenciaId);
    }

    /**
     * Deletar Envolvido
     * DELETE /api/rat/{ocorrencia}/envolvidos/{id}
     */
    public function destroyEnvolvidos($ocorrenciaId, $id): JsonResponse
    {
        try {
            $envolvido = RatRelatoEnvolvidos::where('ocorrencia_id', $ocorrenciaId)
                ->findOrFail($id);

            $envolvido->delete();

            return response()->json([
                'success' => true,
                'message' => 'Envolvido removido com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover envolvido: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // RECURSOS EMPREGADOS
    // ========================================================================

    /**
     * Listagem de Recursos
     * GET /api/rat/{ocorrencia}/recursos
     */
    public function indexRecursos($ocorrenciaId): JsonResponse
    {
        try {
            $recursos = RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)
                ->with('operacionais', 'agentes')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $recursos,
                'count' => $recursos->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar recursos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Armazenar ou Atualizar Recurso
     * POST /api/rat/{ocorrencia}/recursos
     */
    public function storeRecursos(Request $request, $ocorrenciaId): JsonResponse
    {
        try {
            $dto = RatRecursoDTO::fromArray($request->all());
            $recurso = $this->writeService->saveRecurso((string) $ocorrenciaId, $dto);

            return response()->json([
                'success' => true,
                'message' => 'Recurso salvo com sucesso',
                'data' => $recurso,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar recurso: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar Recurso
     * PUT /api/rat/{ocorrencia}/recursos/{id}
     */
    public function updateRecursos(Request $request, $ocorrenciaId, $id): JsonResponse
    {
        $data = $request->all();
        $data['id'] = $id;
        return $this->storeRecursos(new Request($data), $ocorrenciaId);
    }

    /**
     * Deletar Recurso
     * DELETE /api/rat/{ocorrencia}/recursos/{id}
     */
    public function destroyRecursos($ocorrenciaId, $id): JsonResponse
    {
        try {
            $recurso = RatRelatoRecurso::where('ocorrencia_id', $ocorrenciaId)
                ->findOrFail($id);

            $recurso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Recurso removido com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover recurso: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // VISTORIA
    // ========================================================================

    /**
     * Exibir Vistoria
     * GET /api/rat/{ocorrencia}/vistoria
     */
    public function showVistoria($ocorrenciaId): JsonResponse
    {
        try {
            $vistoria = RatRelatoVistoria::where('ocorrencia_id', $ocorrenciaId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $vistoria,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vistoria não encontrada',
            ], 404);
        }
    }

    /**
     * Armazenar ou Atualizar Vistoria
     * POST /api/rat/{ocorrencia}/vistoria
     */
    public function storeVistoria(Request $request, $ocorrenciaId): JsonResponse
    {
        try {
            $dto = RatVistoriaDTO::fromArray($request->all());
            $vistoria = $this->writeService->saveVistoria((string) $ocorrenciaId, $dto);

            return response()->json([
                'success' => true,
                'message' => 'Vistoria salva com sucesso',
                'data' => $vistoria,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar vistoria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar Vistoria
     * PUT /api/rat/{ocorrencia}/vistoria/{id}
     */
    public function updateVistoria(Request $request, $ocorrenciaId, $id): JsonResponse
    {
        return $this->storeVistoria($request, $ocorrenciaId);
    }

    // ========================================================================
    // HISTÓRICO
    // ========================================================================

    /**
     * Exibir Histórico da Ocorrência
     * GET /api/rat/{ocorrencia}/historico
     */
    public function showHistorico($ocorrenciaId): JsonResponse
    {
        try {
            $historico = RatOcorrenciaHistorico::where('ocorrencia_id', $ocorrenciaId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $historico,
                'count' => $historico->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter histórico: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Armazenar Histórico/Descrição
     * POST /api/rat/{ocorrencia}/historico
     */
    public function storeHistorico(Request $request, $ocorrenciaId): JsonResponse
    {
        try {
            $dto = RatHistoricoDTO::fromArray($request->all());
            $this->writeService->saveHistorico((string) $ocorrenciaId, $dto);

            return response()->json([
                'success' => true,
                'message' => 'Histórico salvo com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar histórico: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // ANEXOS/ATTACHMENTS
    // ========================================================================

    /**
     * Armazenar Anexo/Attachment
     * POST /api/rat/{ocorrencia}/attachments
     */
    public function storeAttachment(Request $request, $ocorrenciaId): JsonResponse
    {
        $validated = $request->validate([
            'arquivo' => 'required|file|max:10240',
            'tipo' => 'required|string|in:imagem,documento,video,audio',
            'descricao' => 'nullable|string',
        ]);

        try {
            $attachment = $this->attachmentService->upload(
                $request->file('arquivo'),
                $ocorrenciaId,
                $validated['tipo'],
                $validated['descricao'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Arquivo enviado com sucesso',
                'data' => $attachment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar arquivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar Anexo
     * DELETE /api/rat/{ocorrencia}/attachments/{id}
     */
    public function destroyAttachment($ocorrenciaId, $id): JsonResponse
    {
        try {
            $this->attachmentService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Arquivo removido com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover arquivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // EXPORT & STATISTICS
    // ========================================================================

    /**
     * Exportar RATs
     * GET /api/rat/export
     */
    public function export(Request $request): JsonResponse
    {
        $format = $request->query('format', 'pdf'); // pdf, excel, csv

        try {
            $file = $this->exportService->export(
                $request->query('filters', []),
                $format
            );

            return response()->json([
                'success' => true,
                'message' => 'Arquivo gerado com sucesso',
                'download_url' => $file,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar arquivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter Estatísticas
     * GET /api/rat/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->statisticsService->getAll();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage(),
            ], 500);
        }
    }
}
