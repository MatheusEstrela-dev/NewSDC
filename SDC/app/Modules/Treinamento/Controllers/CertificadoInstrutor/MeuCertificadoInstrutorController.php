<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\CertificadoInstrutor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CertificadoInstrutorService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * O professor abre o proprio certificado de instrutor. A primeira abertura
 * emite; as seguintes devolvem o mesmo certificado (mesmo codigo e data).
 */
class MeuCertificadoInstrutorController extends Controller
{
    public function __construct(private readonly CertificadoInstrutorService $certificados) {}

    public function __invoke(Request $request, Treinamento $treinamento): RedirectResponse
    {
        $usuario = $request->user();

        try {
            $certificado = $this->certificados->emitirParaProfessor($treinamento, $usuario, $usuario);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('treinamentos.certificados-instrutor.imprimir', $certificado);
    }
}
