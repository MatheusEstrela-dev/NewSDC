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
}

