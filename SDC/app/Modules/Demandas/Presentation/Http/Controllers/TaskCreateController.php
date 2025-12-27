<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Application\UseCases\CreateTaskUseCase;
use App\Modules\Demandas\Presentation\Http\Requests\CreateTaskRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: Criar Nova Demanda
 */
class TaskCreateController extends Controller
{
    public function __construct(
        private readonly CreateTaskUseCase $createTaskUseCase
    ) {
    }

    /**
     * Exibir formulário de criação
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Demandas/DemandasCreate', [
            'categorias' => $this->getCategorias(),
            'tipos' => [
                'incidente' => 'Incidente',
                'solicitacao' => 'Solicitação de Serviço',
            ],
        ]);
    }

    /**
     * Salvar nova demanda
     */
    public function store(CreateTaskRequest $request)
    {
        try {
            $validated = $request->validated();

            $task = $this->createTaskUseCase->execute($validated, $request->user());

            return redirect()
                ->route('demandas.show', $task->id)
                ->with('success', "Demanda {$task->protocolo} criada com sucesso!");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Erro ao criar demanda. Por favor, tente novamente.');
        }
    }

    /**
     * Retorna lista de categorias disponíveis
     */
    private function getCategorias(): array
    {
        return [
            'Hardware' => [
                'Computador',
                'Impressora',
                'Monitor',
                'Periféricos',
            ],
            'Software' => [
                'Sistema Operacional',
                'Aplicativos',
                'Licenças',
            ],
            'Rede' => [
                'Conectividade',
                'Wi-Fi',
                'VPN',
            ],
            'Acessos' => [
                'Criação de Usuário',
                'Permissões',
                'Resetar Senha',
            ],
        ];
    }
}
