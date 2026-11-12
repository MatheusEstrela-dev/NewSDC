<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Services\CertificadoService;
use DomainException;

class CertificadoReemitirController extends Controller
{
    public function __construct(
        private readonly CertificadoService $certificadoService
    ) {
    }

    public function __invoke(Certificado $certificado)
    {
        try {
            $this->certificadoService->reemitir($certificado);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Certificado reemitido.');
    }
}
