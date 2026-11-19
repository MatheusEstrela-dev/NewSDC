<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\CertificadoInstrutor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\CertificadoInstrutor;
use App\Modules\Treinamento\Services\ImpressaoDeCertificado;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Impressao do certificado de instrutor: o proprio instrutor ou quem baixa certificados. */
class CertificadoInstrutorImprimirController extends Controller
{
    public function __construct(private readonly ImpressaoDeCertificado $impressao) {}

    public function __invoke(Request $request, CertificadoInstrutor $certificadoInstrutor): View
    {
        $usuario = $request->user();

        abort_unless(
            $certificadoInstrutor->pertenceA($usuario) || $usuario->can('treinamento.certificados.download'),
            403,
        );

        $certificadoInstrutor->loadMissing('treinamento');

        return view('treinamento.certificado-print', $this->impressao->dados(
            ImpressaoDeCertificado::INSTRUTOR,
            $certificadoInstrutor->treinamento,
            $certificadoInstrutor->nome,
            $certificadoInstrutor->codigo_verificacao,
            $certificadoInstrutor->emitido_em,
        ));
    }
}
