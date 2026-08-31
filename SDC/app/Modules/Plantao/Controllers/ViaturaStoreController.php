<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Requests\StoreViaturaRequest;
use App\Modules\Plantao\Services\ViaturaService;
use Illuminate\Http\RedirectResponse;

class ViaturaStoreController extends Controller
{
    public function __construct(
        private readonly ViaturaService $viaturaService
    ) {
    }

    public function __invoke(StoreViaturaRequest $request): RedirectResponse
    {
        $this->viaturaService->create($request->validated());

        return back()->with('success', 'Viatura cadastrada.');
    }
}
