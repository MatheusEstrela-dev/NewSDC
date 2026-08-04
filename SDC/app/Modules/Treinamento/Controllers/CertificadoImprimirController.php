<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Treinamento\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Renderiza o certificado como uma pagina HTML pronta para impressao/"salvar
 * como PDF" pelo navegador - mesmo padrao ja usado pelo modulo Rat
 * (resources/views/rat/print.blade.php), sem depender de biblioteca de PDF
 * no backend.
 */
class CertificadoImprimirController extends Controller
{
    public function __invoke(Request $request, Certificado $certificado): View
    {
        $certificado->loadMissing(['inscricao.inscrito', 'treinamento']);

        // Rota compartilhada entre a area interna (guard "web") e o Portal do
        // Cidadao (guard "cidadao") - resolve o principal autenticado em
        // qualquer um dos dois, o que estiver logado nesta sessao.
        $principal = Auth::guard('cidadao')->user() ?? $request->user();

        abort_unless($principal !== null, 403);

        $ehDono = $certificado->inscricao->inscrito_type === $principal::class
            && $certificado->inscricao->inscrito_id === $principal->id;

        $podeVerQualquer = $principal instanceof User && $principal->can('treinamento.certificados.download');

        abort_unless($ehDono || $podeVerQualquer, 403);

        abort_unless($certificado->status->value === 'GERADO', 404, 'Certificado ainda nao disponivel.');

        return view('treinamento.certificado-print', [
            'treinamento' => $certificado->treinamento,
            'nomeInscrito' => $certificado->inscricao->inscrito?->name ?? '—',
            'percentualFrequencia' => $certificado->inscricao->calcularPercentualFrequencia(),
            'emitidoEm' => $certificado->emitido_em?->format('d/m/Y') ?? now()->format('d/m/Y'),
        ]);
    }
}
