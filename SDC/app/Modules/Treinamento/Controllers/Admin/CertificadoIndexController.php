<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Resources\CertificadoResource;
use Inertia\Inertia;
use Inertia\Response;

class CertificadoIndexController extends Controller
{
    public function __invoke(Treinamento $treinamento): Response
    {
        $certificados = $treinamento->certificados()
            ->with(['inscricao.inscrito', 'treinamento'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Treinamento/Certificados/Index', [
            'treinamento' => [
                'id' => $treinamento->id,
                'titulo' => $treinamento->titulo,
            ],
            'certificados' => CertificadoResource::collection($certificados),
        ]);
    }
}
