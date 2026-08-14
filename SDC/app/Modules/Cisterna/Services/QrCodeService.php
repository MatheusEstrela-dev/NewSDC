<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\Models\CisternaVistoria;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Validation\ValidationException;

/**
 * QR Code impresso no adesivo colado na cisterna instalada. O numero
 * codificado e o `numero_instalacao` da vistoria do fornecedor, e a URL abre
 * a ficha publica do beneficiario.
 *
 * O legado usava simplesoftwareio/simple-qrcode, que nao existe no NewSDC.
 * Reescrito sobre endroid/qr-code, seguindo Treinamento\Services\
 * GeradorQrCodeService.
 *
 * As tres features de PDF do legado (QR individual, QR em lote e folhas de
 * QR vazios) NAO estao aqui: o NewSDC nao tem biblioteca de PDF. Ver spec
 * secao 5.1.1.
 */
class QrCodeService
{
    private const TAMANHO = 300;

    private const MARGEM = 10;

    public function urlDaFicha(int $numeroInstalacao): string
    {
        return route('cisternas.qrcode.ficha', ['numeroInstalacao' => $numeroInstalacao]);
    }

    public function svgDaVistoria(CisternaVistoria $vistoria): string
    {
        $qrCode = $this->construir($vistoria);

        return (new SvgWriter())->write($qrCode)->getString();
    }

    /**
     * @return string binario PNG
     */
    public function pngDaVistoria(CisternaVistoria $vistoria): string
    {
        $qrCode = $this->construir($vistoria);

        return (new PngWriter())->write($qrCode)->getString();
    }

    /**
     * Usado pela rota publica da ficha. O legado fazia um join manual entre
     * sinc_cisterna_rel_fornecedor e sinc_cisterna
     * (CisternaController.php:329).
     */
    public function localizarPorNumero(int $numeroInstalacao): ?CisternaVistoria
    {
        return CisternaVistoria::query()
            ->with(['beneficiario.municipio:id,nome,uf', 'beneficiario.comunidade:id,nome'])
            ->where('numero_instalacao', $numeroInstalacao)
            ->first();
    }

    /**
     * @throws ValidationException quando a vistoria nao tem numero de instalacao
     */
    private function construir(CisternaVistoria $vistoria): QrCode
    {
        if ($vistoria->numero_instalacao === null) {
            throw ValidationException::withMessages([
                'numero_instalacao' => 'Somente a vistoria do fornecedor tem numero de instalacao para gerar QR Code.',
            ]);
        }

        return new QrCode(
            data: $this->urlDaFicha($vistoria->numero_instalacao),
            size: self::TAMANHO,
            margin: self::MARGEM,
        );
    }
}
