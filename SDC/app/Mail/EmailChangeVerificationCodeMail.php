<?php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * E-mail com codigo de 6 digitos para confirmar troca de e-mail.
 * Enviado SEMPRE para o NOVO endereco (posse).
 *
 * PRIMITIVOS no construtor (padrao UserOnboardingMail) — evita
 * SerializesModels + ModelNotFoundException no worker.
 */
class EmailChangeVerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $name,
        public string $newEmail,
        public string $code,
        public string $expiresAtIso,
    ) {
    }

    public static function for(User $user, string $newEmail, string $code, CarbonInterface $expiresAt): self
    {
        return new self(
            userId: $user->id,
            name: $user->name,
            newEmail: $newEmail,
            code: $code,
            expiresAtIso: $expiresAt->toIso8601String(),
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Codigo de verificacao do novo e-mail - SDC',
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
            view: 'emails.email_change_verification',
            with: [
                'name'      => $this->name,
                'newEmail'  => $this->newEmail,
                'code'      => $this->code,
                'expiresAt' => \Carbon\Carbon::parse($this->expiresAtIso),
                'ttlMin'    => 15,
            ],
        );
    }
}
