<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\SalaDeAulaProps;
use Inertia\Inertia;
use Inertia\Response;

class PreviaController extends Controller
{
    public function __construct(private readonly TrilhaService $trilha) {}

    public function __invoke(Treinamento $treinamento): Response
    {
        return Inertia::render(
            'Treinamento/Professor/Previa',
            SalaDeAulaProps::de($treinamento, $this->trilha->estadoPrevia($treinamento), 'previa'),
        );
    }
}
