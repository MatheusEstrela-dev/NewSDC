<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Exceptions\EscalaInvalidaException;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Plantao\Services\EscalaService;
use Illuminate\Http\RedirectResponse;

class EscalaItemDestroyController extends Controller
{
    public function __construct(
        private readonly EscalaService $escalaService
    ) {
    }

    public function __invoke(EscalaItem $item): RedirectResponse
    {
        try {
            $this->escalaService->removerItem($item);
        } catch (EscalaInvalidaException $e) {
            return back()->withErrors(['vaga' => $e->getMessage()]);
        }

        return back()->with('success', 'Vaga removida.');
    }
}
