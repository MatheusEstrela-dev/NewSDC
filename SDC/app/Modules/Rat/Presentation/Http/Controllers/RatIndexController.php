<?php

namespace App\Modules\Rat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Application\UseCases\GetRatStatisticsUseCase;
use App\Modules\Rat\Application\UseCases\ListRatsUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\Export\CsvExportService;

class RatIndexController extends Controller
{
    public function __construct(
        private readonly GetRatStatisticsUseCase $getStatisticsUseCase,
        private readonly ListRatsUseCase $listRatsUseCase,
        private readonly CsvExportService $csvExportService,
        private readonly \App\Modules\Rat\Application\UseCases\DeleteRatUseCase $deleteRatUseCase
    ) {
    }

    public function destroy(string $id): \Illuminate\Http\RedirectResponse
    {
        // #region agent log
        $logData = [
            'location' => 'RatIndexController.php:destroy',
            'message' => 'destroy called',
            'data' => ['id' => $id],
            'timestamp' => now()->timestamp * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'G'
        ];
        Log::info('DEBUG: RatIndex destroy called', $logData);
        $this->writeDebugLog($logData);
        // #endregion

        try {
            $this->authorize('delete', \App\Modules\Rat\Domain\Entities\Rat::class); // Alternativa se houver Policy, se nao usar middleware na rota

            $this->deleteRatUseCase->execute($id);

            return redirect()->route('rat.index')
                ->with('success', 'RAT excluído com sucesso.');
        } catch (\Exception $e) {
            // #region agent log
            $logData = [
                'location' => 'RatIndexController.php:destroy',
                'message' => 'Error in destroy',
                'data' => [
                    'error' => $e->getMessage(),
                    'id' => $id
                ],
                'timestamp' => now()->timestamp * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'G'
            ];
            Log::error('DEBUG: Error in destroy', $logData);
            $this->writeDebugLog($logData);
            // #endregion

            return redirect()->back()
                ->with('error', 'Erro ao excluir RAT: ' . $e->getMessage());
        }
    }


    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        // #region agent log
        $logData = [
            'location' => 'RatIndexController.php:index',
            'message' => 'index called',
            'data' => ['filters' => $request->all()],
            'timestamp' => now()->timestamp * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'F'
        ];
        Log::info('DEBUG: RatIndex index called', $logData);
        $this->writeDebugLog($logData);
        // #endregion
        try {
            $filters = $request->only([
                'protocolo',
                'status',
                'data_inicio',
                'data_fim',
                'ano',
                'municipio',
                'tipo_cobrade',
                'natureza',
                'criado_por',
            ]);

            $statistics = $this->getStatisticsUseCase->execute($filters);
            $ratsResult = $this->listRatsUseCase->executeAsDTO($filters, 15);

            // #region agent log
            Log::info('DEBUG: Data prepared', [
                'rats_count' => count($ratsResult['data']),
                'pagination_total' => $ratsResult['pagination']['total'] ?? 0,
            ]);
            // #endregion

            return Inertia::render('RatIndex', [
                'statistics' => $statistics->toArray(),
                'rats' => $ratsResult['data'],
                'pagination' => $ratsResult['pagination'],
                'filters' => $filters,
                'municipalities' => [], // TODO: Buscar do banco
                'cobradeTypes' => [], // TODO: Buscar do banco
                'years' => range(date('Y'), 2020, -1),
            ]);
        } catch (\Exception $e) {
            // #region agent log
            $logData = [
                'location' => 'RatIndexController.php:index',
                'message' => 'Error in index',
                'data' => [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ],
                'timestamp' => now()->timestamp * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'F'
            ];
            Log::error('DEBUG: Error in index', $logData);
            $this->writeDebugLog($logData);
            // #endregion
            return redirect()->back()->with('error', 'Erro ao carregar RATs. Por favor, tente novamente.');
        }
    }
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only([
            'protocolo',
            'status',
            'data_inicio',
            'data_fim',
            'ano',
            'municipio',
            'tipo_cobrade',
            'natureza',
            'criado_por',
        ]);

        $ratsResult = $this->listRatsUseCase->executeAsDTO($filters, -1);
        $rats = $ratsResult['data'];

        $headers = [
            'ID',
            'Protocolo',
            'Status',
            'Tipo Demanda',
            'Município',
            'Descrição',
            'Data Criação',
        ];

        $mapper = function ($rat) {
            return [
                $rat['id'] ?? '',
                $rat['protocolo'] ?? '',
                $rat['status'] ?? '',
                $rat['tipo_demanda'] ?? '',
                $rat['municipio'] ?? '',
                $rat['descricao'] ?? '',
                $rat['created_at'] ?? '',
            ];
        };

        return $this->csvExportService->export($rats, $headers, $mapper, 'rats_export');
    }

    public function showJson(string $id): \Illuminate\Http\JsonResponse
    {
        // #region agent log
        $logData = [
            'location' => 'RatIndexController.php:showJson',
            'message' => 'showJson called',
            'data' => ['id' => $id],
            'timestamp' => now()->timestamp * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B'
        ];
        Log::info('DEBUG: showJson called', $logData);
        $this->writeDebugLog($logData);
        // #endregion
        try {
            // #region agent log
            $logData = [
                'location' => 'RatIndexController.php:showJson',
                'message' => 'Attempting to find Rat',
                'data' => ['id' => $id],
                'timestamp' => now()->timestamp * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B'
            ];
            Log::info('DEBUG: Attempting to find Rat', $logData);
            $this->writeDebugLog($logData);
            // #endregion
            // Tentar encontrar o RAT no banco
            $rat = \App\Modules\Rat\Domain\Entities\Rat::find($id);
            
            // Se não encontrar, usar dados mockados temporariamente
            if (!$rat) {
                // #region agent log
                $logData = [
                    'location' => 'RatIndexController.php:showJson',
                    'message' => 'Rat not found in DB, using mock data',
                    'data' => ['id' => $id],
                    'timestamp' => now()->timestamp * 1000,
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'B'
                ];
                Log::warning('DEBUG: Rat not found, using mock', $logData);
                $this->writeDebugLog($logData);
                // #endregion
                
                // Retornar dados mockados baseados no ID
                $response = $this->getMockRatData($id);
            } else {
                // #region agent log
                $logData = [
                    'location' => 'RatIndexController.php:showJson',
                    'message' => 'Rat found',
                    'data' => [
                        'id' => $rat->id,
                        'protocolo' => $rat->protocolo,
                        'has_dados_gerais' => !empty($rat->dados_gerais),
                        'has_local' => !empty($rat->local),
                        'rat_keys' => array_keys($rat->toArray())
                    ],
                    'timestamp' => now()->timestamp * 1000,
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'C'
                ];
                Log::info('DEBUG: Rat found', $logData);
                $this->writeDebugLog($logData);
                // #endregion
                
                $ratArray = $rat->toArray();
                
                // Transformar para o formato esperado pelo frontend
                $response = [
                    'id' => $rat->id,
                    'numero_bos' => $rat->protocolo ?? null, // Mapear protocolo para numero_bos
                    'protocolo' => $rat->protocolo ?? null,
                    'status' => $rat->status ?? 'rascunho',
                    'tem_vistoria' => $rat->tem_vistoria ?? false,
                    'dados_gerais' => $rat->dados_gerais ?? [],
                    'dadosGerais' => $rat->dados_gerais ?? [], // Também incluir no formato camelCase
                    'local' => $rat->local ?? [],
                    'endereco' => $rat->endereco ?? [],
                    'comunicacao' => $rat->comunicacao ?? [],
                    'envolvidos' => [], // TODO: Carregar relacionamento quando existir
                    'recursos' => [], // TODO: Carregar relacionamento quando existir
                    'vistoria' => $rat->tem_vistoria ? ($rat->vistoria ?? null) : null, // TODO: Carregar relacionamento quando existir
                    'historico' => [], // TODO: Carregar relacionamento quando existir
                    'created_at' => $rat->created_at?->toISOString() ?? null,
                    'updated_at' => $rat->updated_at?->toISOString() ?? null,
                    'created_by' => $rat->created_by ?? null,
                    'updated_by' => $rat->updated_by ?? null,
                ];
            }
            
            // #region agent log
            $logData = [
                'location' => 'RatIndexController.php:showJson',
                'message' => 'Returning JSON response',
                'data' => [
                    'response_keys' => array_keys($response),
                    'has_numero_bos' => !empty($response['numero_bos']),
                    'has_dados_gerais' => !empty($response['dados_gerais']),
                    'has_envolvidos' => !empty($response['envolvidos']),
                    'has_recursos' => !empty($response['recursos']),
                    'has_vistoria' => !empty($response['vistoria']),
                    'has_historico' => !empty($response['historico']),
                ],
                'timestamp' => now()->timestamp * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'E'
            ];
            Log::info('DEBUG: Returning JSON response', $logData);
            $this->writeDebugLog($logData);
            // #endregion
            return response()->json($response);
        } catch (\Exception $e) {
            // #region agent log
            $logData = [
                'location' => 'RatIndexController.php:showJson',
                'message' => 'Error in showJson',
                'data' => [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'id' => $id,
                    'trace' => $e->getTraceAsString()
                ],
                'timestamp' => now()->timestamp * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B'
            ];
            Log::error('DEBUG: Error in showJson', $logData);
            $this->writeDebugLog($logData);
            // #endregion
            
            // Em caso de erro, tentar retornar dados mockados
            try {
                $response = $this->getMockRatData((int) $id);
                return response()->json($response);
            } catch (\Exception $mockError) {
                return response()->json(['error' => 'RAT não encontrado'], 404);
            }
        }
    }

    // #region agent log
    private function writeDebugLog(array $data): void
    {
        $logDir = 'c:\\Users\\x24679188\\Documents\\GitHub\\sdc\\.cursor';
        $logPath = $logDir . '\\debug.log';
        try {
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logEntry = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            @file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Silently fail - logging is not critical
        }
    }
    
    /**
     * Retorna dados mockados de um RAT para desenvolvimento
     * TODO: Remover quando a tabela do banco estiver populada
     */
    private function getMockRatData(int $id): array
    {
        $year = date('Y');
        $protocolo = sprintf('RAT-%s-%03d', $year, $id);
        
        return [
            'id' => $id,
            'numero_bos' => $protocolo,
            'protocolo' => $protocolo,
            'status' => 'em_andamento',
            'tem_vistoria' => false,
            'dados_gerais' => [
                'data_fato' => now()->subDays(2)->toIso8601String(),
                'data_inicio_atividade' => now()->subDays(2)->toIso8601String(),
                'data_termino_atividade' => now()->subHours(5)->toIso8601String(),
                'nat_cobrade_id' => 'Q03027',
                'nat_nome_operacao' => 'Inundação',
                'local_municipio' => 'Belo Horizonte/MG',
            ],
            'dadosGerais' => [
                'data_fato' => now()->subDays(2)->toIso8601String(),
                'data_inicio_atividade' => now()->subDays(2)->toIso8601String(),
                'data_termino_atividade' => now()->subHours(5)->toIso8601String(),
                'nat_cobrade_id' => 'Q03027',
                'nat_nome_operacao' => 'Inundação',
                'local_municipio' => 'Belo Horizonte/MG',
            ],
            'local' => [
                'municipio' => 'Belo Horizonte',
                'uf' => 'MG',
                'pais_id' => 1,
            ],
            'endereco' => [
                'logradouro' => 'Rua Exemplo',
                'numero' => '123',
                'bairro' => 'Centro',
                'cep' => '30000-000',
            ],
            'comunicacao' => [],
            'envolvidos' => [
                [
                    'id' => 1,
                    'nome' => 'João Silva',
                    'tipo' => 'vítima',
                    'documento' => '123.456.789-00',
                ],
            ],
            'recursos' => [
                [
                    'id' => 1,
                    'tipo' => 'Veículo',
                    'descricao' => 'Ambulância',
                    'componentesGuarnicao' => [
                        [
                            'id' => 1,
                            'nome' => 'Maria Santos',
                            'funcao' => 'Médico',
                        ],
                    ],
                ],
            ],
            'vistoria' => null,
            'historico' => [
                [
                    'id' => 1,
                    'tipo' => 'criacao',
                    'titulo' => 'RAT criado',
                    'descricao' => 'Registro de Atendimento Técnico criado',
                    'data' => now()->subDays(2)->toIso8601String(),
                    'autor' => 'Sistema',
                ],
                [
                    'id' => 2,
                    'tipo' => 'atualizacao',
                    'titulo' => 'Dados atualizados',
                    'descricao' => 'Informações do RAT foram atualizadas',
                    'data' => now()->subHours(5)->toIso8601String(),
                    'autor' => 'João Silva',
                ],
            ],
            'created_at' => now()->subDays(2)->toIso8601String(),
            'updated_at' => now()->subHours(5)->toIso8601String(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
    // #endregion
}

