<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Resources\FichaPublicaResource;
use App\Modules\Cisterna\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Ficha publica lida pelo QR Code colado na cisterna, e download do QR.
 *
 * As tres features de PDF do legado (pdfIndividual em lote, baixarQRCodes e
 * gerarQRCodesVazios) NAO estao aqui: o NewSDC nao tem biblioteca de PDF.
 * Ver spec secao 5.1.1. As rotas cisternas.qrcode.pdf-individual,
 * .pdf-em-lote e .folhas-vazias respondem 501 ate a decisao ser tomada.
 */
class QrCodeController extends Controller
{
    public function __construct(
        private readonly QrCodeService $service,
    ) {}

    /**
     * Rota publica, sem autenticacao — como no legado: quem esta em campo le
     * o adesivo com o celular.
     */
    public function ficha(int $numeroInstalacao): Response
    {
        $vistoria = $this->service->localizarPorNumero($numeroInstalacao);

        abort_if($vistoria === null, SymfonyResponse::HTTP_NOT_FOUND);

        return Inertia::render('Cisterna/QrCode/Ficha', [
            'numero_instalacao' => $numeroInstalacao,
            // Resource MINIMO, e nao o BeneficiarioResource: a rota e publica e o
            // Inertia embute as props no HTML. Ver FichaPublicaResource.
            'beneficiario' => FichaPublicaResource::make($vistoria->beneficiario)->resolve(),
            'instalada_em' => $vistoria->data_relatorio?->toDateString(),
        ]);
    }

    /**
     * Dados do QR de um beneficiario, para o modal da listagem.
     *
     * O QR pertence a uma VISTORIA, e nao ao cadastro: o que ele codifica e o
     * numero de instalacao, atribuido quando a etapa do fornecedor abre. Um
     * beneficiario sem vistoria numerada nao tem adesivo para colar, e o modal
     * precisa dizer isso em vez de mostrar quadrado vazio.
     *
     * Devolve SVG, e nao PNG: escala sem borrar na tela e na impressao, e o
     * download continua em PNG pela rota que ja existia.
     */
    public function dadosDoBeneficiario(CisternaBeneficiario $beneficiario): JsonResponse
    {
        $this->authorize('view', $beneficiario);

        $vistoria = $beneficiario->vistorias()
            ->whereNotNull('numero_instalacao')
            ->orderBy('numero_instalacao')
            ->first();

        if ($vistoria === null) {
            return response()->json([
                'message' => 'Este cadastro ainda nao tem numero de instalacao. '
                    .'O numero e atribuido ao abrir o relatorio do fornecedor.',
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'numero_instalacao' => $vistoria->numero_instalacao,
            'beneficiario' => $beneficiario->nome,
            'url' => $this->service->urlDaFicha((int) $vistoria->numero_instalacao),
            'svg' => $this->service->svgDaVistoria($vistoria),
            'download' => route('cisternas.qrcode.pdf-individual', $vistoria->id),
        ]);
    }

    /**
     * PNG do QR Code de uma vistoria, para baixar e imprimir individualmente.
     */
    public function pdfIndividual(CisternaVistoria $vistoria): HttpResponse
    {
        $this->authorize('view', $vistoria);

        $png = $this->service->pngDaVistoria($vistoria);

        return response($png, SymfonyResponse::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename=qrcode-cisterna-'.$vistoria->numero_instalacao.'.png',
        ]);
    }

    /**
     * Folha de adesivos com varios QR Codes. Depende de biblioteca de PDF,
     * ausente no NewSDC.
     */
    public function pdfEmLote(): HttpResponse
    {
        return $this->naoImplementado();
    }

    /**
     * Folhas de QR Codes vazios por faixa de numeracao. Depende de biblioteca
     * de PDF, ausente no NewSDC.
     */
    public function folhasVazias(): HttpResponse
    {
        return $this->naoImplementado();
    }

    private function naoImplementado(): HttpResponse
    {
        return response(
            [
                'message' => 'Impressao em lote de QR Codes ainda nao disponivel: '
                    .'depende de biblioteca de PDF a ser definida para o NewSDC.',
            ],
            SymfonyResponse::HTTP_NOT_IMPLEMENTED,
        );
    }
}
