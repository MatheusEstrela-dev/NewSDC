<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\EscalaInvalidaException;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Plantao\Requests\UpdateEscalaItemRequest;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\RedirectResponse;

class EscalaItemUpdateController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(UpdateEscalaItemRequest $request, EscalaItem $item): RedirectResponse
    {
        try {
            $item = $this->escalaService->trocarPlantonista(
                $item,
                (int) $request->validated()['plantonista_id'],
            );
        } catch (EscalaInvalidaException $e) {
            return back()->withErrors(['vaga' => $e->getMessage()]);
        }

        $alertas = $this->escalaService->alertasDeDescanso($item);

        $resposta = back()->with('success', 'Plantonista da vaga alterado.');

        return $alertas === []
            ? $resposta
            : $resposta->with('warning', implode(' ', $alertas));
    }
}
