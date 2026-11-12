<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Requests\CancelarCertificadoRequest;
use App\Modules\Treinamento\Services\CertificadoService;
use DomainException;

class CertificadoCancelarController extends Controller
{
    public function __construct(private readonly CertificadoService $certificadoService) {}

    public function __invoke(CancelarCertificadoRequest $request, Certificado $certificado)
    {
        try {
            $this->certificadoService->cancelar($certificado, $request->user(), $request->validated('motivo'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Certificado cancelado.');
    }
}
