<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa o aluno (servidor ou cidadao) de que o certificado ficou disponivel.
 * Carrega so textos (sem models): o e-mail nao depende de dois bancos na fila.
 */
class CertificadoDisponivelMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nomeInscrito,
        public readonly string $curso,
        public readonly ?string $rotuloVersao,
        public readonly string $dataConclusao,
        public readonly string $urlMeusCursos,
        public readonly string $urlValidacao,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Seu certificado de {$this->curso} está disponível");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.treinamento.certificado_disponivel');
    }
}
