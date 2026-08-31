<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\RedirectResponse;

class ViaturaDestroyController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(Viatura $viatura): RedirectResponse
    {
        $this->viaturaService->delete($viatura->id);

        return back()->with('success', 'Viatura removida.');
    }
}
