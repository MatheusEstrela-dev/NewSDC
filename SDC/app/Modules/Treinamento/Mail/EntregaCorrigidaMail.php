<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa o aluno de que o professor corrigiu a entrega do projeto. Carrega so
 * textos (sem models): o e-mail nao depende de dois bancos na fila.
 */
class EntregaCorrigidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nomeInscrito,
        public readonly string $curso,
        public readonly string $item,
        public readonly bool $aprovada,
        public readonly ?string $parecer,
    ) {}

    public function envelope(): Envelope
    {
        $resultado = $this->aprovada ? 'aprovada' : 'devolvida';

        return new Envelope(subject: "Entrega {$resultado}: {$this->item}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.treinamento.entrega_corrigida');
    }
}
