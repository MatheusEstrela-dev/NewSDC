<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * E-mail de boas-vindas enviado quando o admin cadastra um novo usuario.
 * Carrega CPF + senha provisoria em destaque, com aviso de expiracao (24h).
 *
 * Reusa o design do template de password reset (Oxford Blue + Orange) via
 * resources/views/emails/user_onboarding.blade.php e o mesmo CID 'logo-cedec'.
 *
 * Recebe PRIMITIVOS (nao o User model) — evita SerializesModels que tentava
 * User::find($id) no worker e falhava com ModelNotFoundException mesmo com
 * afterCommit() aplicado. Padrao identico ao reset password (que ja funciona)
 * em ResetPassword::toMailUsing no AppServiceProvider.
 */
class UserOnboardingMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $name,
        public string $emailAddress,
        public ?string $cpf,
        public string $plainPassword,
        public ?string $expiresAtIso,
    ) {
    }

    /**
     * Factory de conveniencia para chamadas no codigo (mantém DX limpo
     * sem precisar repetir a serializacao em cada caller).
     */
    public static function forUser(User $user, string $plainPassword): self
    {
        return new self(
            userId: $user->id,
            name: $user->name,
            emailAddress: $user->email,
            cpf: $user->cpf,
            plainPassword: $plainPassword,
            expiresAtIso: $user->pending_expires_at?->toIso8601String(),
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo ao SDC - dados de acesso',
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
                'name' => $this->name,
                'cpf' => $this->formatCpf($this->cpf),
                'email' => $this->emailAddress,
                'plainPassword' => $this->plainPassword,
                'expiresAt' => $this->expiresAtIso ? \Carbon\Carbon::parse($this->expiresAtIso) : null,
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
