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
 * Aviso enviado para o e-mail ATUAL quando alguem pede troca.
 * Diferencia copy quando o pedido foi feito por um admin.
 */
class EmailChangeNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $name,
        public string $currentEmail,
        public string $newEmailMasked,
        public ?string $byAdminName,
    ) {
    }

    public static function for(User $user, string $newEmail, ?User $byAdmin = null): self
    {
        return new self(
            userId: $user->id,
            name: $user->name,
            currentEmail: $user->email,
            newEmailMasked: self::maskEmail($newEmail),
            byAdminName: $byAdmin?->name,
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido de troca do seu e-mail - SDC',
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
            view: 'emails.email_change_notice',
            with: [
                'name'           => $this->name,
                'newEmailMasked' => $this->newEmailMasked,
                'byAdminName'    => $this->byAdminName,
                'passwordResetUrl' => url(route('password.request', absolute: false)),
            ],
        );
    }

    /**
     * "matheus.estrela@gmail.com" -> "ma***@gmail.com"
     */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + ['', ''];
        if ($local === '' || $domain === '') {
            return $email;
        }
        $visible = substr($local, 0, 2);
        return "{$visible}***@{$domain}";
    }
}
