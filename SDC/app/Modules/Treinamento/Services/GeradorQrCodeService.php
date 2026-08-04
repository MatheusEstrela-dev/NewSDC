<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Gera o QR Code do ingresso (usado no e-mail de confirmacao de inscricao em
 * treinamentos presenciais). O token codificado e o mesmo `qr_code_token` da
 * Inscricao, lido pelo scanner em Components/Molecules/Treinamento/QrScanner.vue.
 */
class GeradorQrCodeService
{
    public function gerarPng(string $token): string
    {
        $qrCode = new QrCode(data: $token, size: 300, margin: 10);
        $writer = new PngWriter();

        return $writer->write($qrCode)->getString();
    }
}
