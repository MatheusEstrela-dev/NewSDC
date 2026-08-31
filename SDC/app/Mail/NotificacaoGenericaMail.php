<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Versao por e-mail de qualquer notificacao do sistema.
 *
 * Recebe primitivos, e nao a NotificacaoSpec, pelo mesmo motivo dos demais
 * mailables do projeto (UserOnboardingMail, PaeNotificacaoMail): o objeto e
 * serializado na fila, entao quanto menos ele carrega, menos quebra quando a
 * classe do DTO muda entre o enfileiramento e a execucao.
 *
 * O assunto leva o nome do modulo ("[SDC] RAT: ...") porque na caixa de entrada
 * o usuario nao tem o sino ao lado para saber de onde veio.
 */
class NotificacaoGenericaMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $titulo,
        public string $mensagem,
        public string $tipo = 'info',
        public ?string $acaoUrl = null,
        public ?string $acaoTexto = null,
        public string $moduloLabel = 'SDC',
    ) {}

    public function envelope(): Envelope
    {
        $prefixo = $this->tipo === 'urgent' ? '[URGENTE] ' : '';

        return new Envelope(
            subject: "{$prefixo}[SDC] {$this->moduloLabel}: {$this->titulo}",
            using: [
                // Mesmo CID usado pelos outros e-mails institucionais.
                function (Email $message): void {
                    $logoPath = public_path('imgs/logo_dc.png');
                    if (is_file($logoPath)) {
                        $message->embedFromPath($logoPath, 'logo-cedec', 'image/png');
                    }
                },
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacao_generica',
        );
    }
}
