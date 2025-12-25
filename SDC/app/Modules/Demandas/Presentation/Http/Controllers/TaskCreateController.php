<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Demandas\Application\UseCases\CreateTaskUseCase;
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:incidente,solicitacao',
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'categoria' => 'nullable|string|max:100',
            'subcategoria' => 'nullable|string|max:100',
            'urgencia' => 'nullable|in:alta,media,baixa',
            'impacto' => 'nullable|in:alto,medio,baixo',
        ]);

        $task = $this->createTaskUseCase->execute($validated, $request->user());

        return redirect()
            ->route('demandas.show', $task->id)
            ->with('success', "Demanda {$task->protocolo} criada com sucesso!");
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
