<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa quem se formou numa versao anterior de que o curso ganhou versao nova.
 * Carrega so textos (sem models): o e-mail nao depende de dois bancos na fila.
 */
class NovaVersaoDisponivelMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nomeInscrito,
        public readonly string $curso,
        public readonly string $rotulo,
        public readonly ?string $notas,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Versao {$this->rotulo} disponivel: {$this->curso}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.treinamento.nova_versao_disponivel');
    }
}
