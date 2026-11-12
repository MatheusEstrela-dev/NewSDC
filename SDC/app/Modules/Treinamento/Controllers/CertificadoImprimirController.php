<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Services\CertificadoService;
use App\Modules\Treinamento\Services\GeradorQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Renderiza o certificado como uma pagina HTML pronta para impressao/"salvar
 * como PDF" pelo navegador - mesmo padrao ja usado pelo modulo Rat
 * (resources/views/rat/print.blade.php), sem depender de biblioteca de PDF
 * no backend.
 */
class CertificadoImprimirController extends Controller
{
    public function __construct(
        private readonly CertificadoService $certificados,
        private readonly GeradorQrCodeService $qrCode,
    ) {}

    public function __invoke(Request $request, Certificado $certificado): View
    {
        $certificado->loadMissing(['inscricao.inscrito', 'treinamento']);

        // Rota compartilhada entre a area interna (guard "web") e o Portal do
        // Cidadao (guard "cidadao") - resolve o principal autenticado em
        // qualquer um dos dois, o que estiver logado nesta sessao.
        $principal = Auth::guard('cidadao')->user() ?? $request->user();

        abort_unless($principal !== null, 403);

        // Logica de autorizacao (dono da inscricao OU permissao de staff)
        // centralizada em CertificadoPolicy::view.
        abort_unless(Gate::forUser($principal)->allows('view', $certificado), 403);

        abort_unless($certificado->status->value === 'GERADO', 404, 'Certificado ainda nao disponivel.');

        // Certificado emitido antes do codigo existir (ate o comando retroativo rodar).
        $this->certificados->garantirCodigo($certificado);

        return view('treinamento.certificado-print', [
            'treinamento' => $certificado->treinamento,
            'nomeInscrito' => $certificado->inscricao->inscrito?->name ?? '—',
            'percentualFrequencia' => $certificado->inscricao->calcularPercentualFrequencia(),
            'codigoVerificacao' => $certificado->codigo_verificacao,
            'urlValidacao' => route('certificados.validar', $certificado->codigo_verificacao),
            'qrValidacao' => $this->qrDaValidacao($certificado),
            'aproveitamento' => $certificado->aproveitamento,
            'emitidoEm' => $certificado->emitido_em?->format('d/m/Y') ?? now()->format('d/m/Y'),
        ]);
    }

    private function qrDaValidacao(Certificado $certificado): string
    {
        $png = $this->qrCode->gerarPng(route('certificados.validar', $certificado->codigo_verificacao));

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
