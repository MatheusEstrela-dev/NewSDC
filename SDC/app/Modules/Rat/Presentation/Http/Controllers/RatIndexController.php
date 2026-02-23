<?php

namespace App\Modules\Rat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Application\DTOs\DeleteRatDTO;
use App\Modules\Rat\Application\DTOs\FinalizeRatDTO;
use App\Modules\Rat\Application\Services\RatService;
use App\Modules\Rat\Application\UseCases\GetRatStatisticsUseCase;
use App\Modules\Rat\Application\UseCases\ListRatsUseCase;
use App\Services\Export\CsvExportService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RatIndexController extends Controller
{
    public function __construct(
        private readonly GetRatStatisticsUseCase $getStatisticsUseCase,
        private readonly ListRatsUseCase $listRatsUseCase,
        private readonly CsvExportService $csvExportService,
        private readonly RatService $ratService
    ) {
    }

    /**
     * Exclui um RAT.
     * Validacao tripla: Gate (ActionPolicy) + Service (regras de dominio).
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        try {
            $rat = \App\Modules\Rat\Domain\Entities\Rat::findOrFail($id);

            Gate::authorize('rat.delete', $rat);

            $dto = new DeleteRatDTO(
                id: $id,
                userId: auth()->id(),
                motivo: $request->input('motivo')
            );

            $this->ratService->delete($dto);

            return redirect()->route('rat.index')
                ->with('success', 'RAT excluido com sucesso.');
        } catch (DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->back()
                ->with('error', 'Voce nao tem permissao para excluir este RAT.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir RAT: ' . $e->getMessage());
        }
    }

    /**
     * Finaliza um RAT.
     * Validacao tripla: Gate (ActionPolicy) + Service (regras de dominio).
     */
    public function finalize(Request $request, string $id): RedirectResponse
    {
        try {
            $rat = \App\Modules\Rat\Domain\Entities\Rat::findOrFail($id);

            Gate::authorize('rat.finalize', $rat);

            $dto = new FinalizeRatDTO(
                id: $id,
                userId: auth()->id(),
                observacao: $request->input('observacao')
            );

            $this->ratService->finalize($dto);

            return redirect()->route('rat.index')
                ->with('success', 'RAT finalizado com sucesso.');
        } catch (DomainException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->back()
                ->with('error', 'Voce nao tem permissao para finalizar este RAT.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao finalizar RAT: ' . $e->getMessage());
        }
    }


    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
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
        try {
            // Tentar encontrar o RAT no banco
            $rat = \App\Modules\Rat\Domain\Entities\Rat::find($id);
            
            // Se não encontrar, usar dados mockados temporariamente
            if (!$rat) {
                // Retornar dados mockados baseados no ID
                $response = $this->getMockRatData($id);
            } else {
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
            
            return response()->json($response);
        } catch (\Exception $e) {
            // Em caso de erro, tentar retornar dados mockados
            try {
                $response = $this->getMockRatData((int) $id);
                return response()->json($response);
            } catch (\Exception $mockError) {
                return response()->json(['error' => 'RAT não encontrado'], 404);
            }
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
}
