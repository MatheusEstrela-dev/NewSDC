<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Codigo de 6 digitos que confirma a posse do e-mail informado no cadastro
 * publico do Portal de Treinamentos. Enquanto ele nao for digitado, a conta do
 * cidadao existe mas nao autentica.
 *
 * PRIMITIVOS no construtor (padrao do modulo, ver InscricaoConfirmadaMail) -
 * evita SerializesModels + ModelNotFoundException no worker se o cadastro
 * pendente for sobrescrito antes da fila processar.
 */
class CidadaoVerificacaoCodigoMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $nome,
        public string $email,
        public string $codigo,
        public string $expiraEmIso,
    ) {
    }

    public static function para(string $nome, string $email, string $codigo, CarbonInterface $expiraEm): self
    {
        return new self(
            nome: $nome,
            email: $email,
            codigo: $codigo,
            expiraEmIso: $expiraEm->toIso8601String(),
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Codigo de confirmacao do seu cadastro - Portal de Treinamentos',
            using: [
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
            view: 'emails.treinamento.verificacao_codigo',
            with: [
                'nome' => $this->nome,
                'email' => $this->email,
                'codigo' => $this->codigo,
                'expiraEm' => \Carbon\Carbon::parse($this->expiraEmIso)->format('d/m/Y H:i'),
                'ttlMin' => \App\Modules\Treinamento\Models\CidadaoEmailVerificacao::TTL_MINUTES,
            ],
        );
    }
}
