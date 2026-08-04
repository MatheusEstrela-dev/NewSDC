<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Services\CertificadoService;

class CertificadoReemitirController extends Controller
{
    public function __construct(
        private readonly CertificadoService $certificadoService
    ) {
    }

    public function __invoke(Certificado $certificado)
    {
        $this->certificadoService->reemitir($certificado);

        return back()->with('success', 'Certificado reemitido.');
    }
}
