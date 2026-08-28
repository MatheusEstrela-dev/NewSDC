<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Models\Plantonista;
use App\Modules\Plantao\Requests\StorePlantonistaRequest;
use Illuminate\Http\RedirectResponse;

class PlantonistaStoreController extends Controller
{
    public function __invoke(StorePlantonistaRequest $request): RedirectResponse
    {
        $plantonista = Plantonista::create($request->validated() + ['ativo' => true]);

        return back()->with(
            'success',
            $plantonista->nomeComPosto().' agora pode ser escalado.'
        );
    }
}
