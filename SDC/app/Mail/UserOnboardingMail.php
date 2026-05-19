<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

/**
 * E-mail de boas-vindas enviado quando o admin cadastra um novo usuario.
 * Carrega CPF + senha provisoria em destaque, com aviso de expiracao (24h).
 *
 * Reusa o design do template de password reset (Oxford Blue + Orange) via
 * resources/views/emails/user_onboarding.blade.php e o mesmo CID 'logo-cedec'.
 */
class UserOnboardingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao SDC — dados de acesso',
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
            view: 'emails.user_onboarding',
            with: [
                'name' => $this->user->name,
                'cpf' => $this->formatCpf($this->user->cpf),
                'email' => $this->user->email,
                'plainPassword' => $this->plainPassword,
                'expiresAt' => $this->user->pending_expires_at,
                'loginUrl' => url(route('login', absolute: false)),
            ],
        );
    }

    private function formatCpf(?string $cpf): ?string
    {
        if ($cpf === null || strlen($cpf) !== 11) {
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
}
