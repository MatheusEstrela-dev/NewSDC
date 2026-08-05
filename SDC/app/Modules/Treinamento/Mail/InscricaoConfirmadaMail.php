<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Mail;

use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\GeradorQrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Enviado apenas para inscricoes em treinamentos PRESENCIAIS (RF03) - o QR
 * Code do ingresso e anexado como imagem PNG, gerado por GeradorQrCodeService.
 * Treinamentos ONLINE nao usam QR - o participante confirma presenca direto
 * na tela dele (ver PresencaService::autoconfirmar()).
 */
class InscricaoConfirmadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Inscricao $inscricao,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Inscrição confirmada: {$this->inscricao->treinamento->titulo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.treinamento.inscricao_confirmada',
            with: [
                'treinamento' => $this->inscricao->treinamento,
                'nomeInscrito' => $this->inscricao->inscrito?->name,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $png = app(GeradorQrCodeService::class)->gerarPng($this->inscricao->qr_code_token);

        return [
            Attachment::fromData(fn () => $png, 'ingresso-qrcode.png')->withMime('image/png'),
        ];
    }
}
