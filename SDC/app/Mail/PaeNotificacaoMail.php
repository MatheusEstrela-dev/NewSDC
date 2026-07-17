<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notificacao do ciclo PAE (1/2/3) enviada ao coordenador do empreendimento.
 * Recebe primitivos (nao models) — mesmo padrao do UserOnboardingMail.
 */
class PaeNotificacaoMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $protocoloNumero,
        public string $empreendimentoNome,
        public int $ciclo,
        public string $numSei,
        public string $dtNotificacao,
        public string $prazoFinal,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "PAE {$this->protocoloNumero} - Notificacao {$this->ciclo} de 3",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pae_notificacao',
        );
    }
}
