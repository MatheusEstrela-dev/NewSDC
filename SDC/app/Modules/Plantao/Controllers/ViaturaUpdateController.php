<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Requests\UpdateViaturaRequest;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\RedirectResponse;

class ViaturaUpdateController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(UpdateViaturaRequest $request, Viatura $viatura): RedirectResponse
    {
        $this->viaturaService->update($viatura->id, $request->validated());

        return back()->with('success', 'Viatura atualizada.');
    }
}
