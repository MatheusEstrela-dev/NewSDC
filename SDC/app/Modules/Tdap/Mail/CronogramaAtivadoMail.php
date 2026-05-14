<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Mail;

use App\Modules\Tdap\Models\Cronograma;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CronogramaAtivadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Cronograma $cronograma,
    ) {}

    public function envelope(): Envelope
    {
        $numero = $this->cronograma->numero ?? 'N/D';

        return new Envelope(
            subject: "Cronograma TDAP nº {$numero} liberado",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tdap.cronograma_ativado',
            with: [
                'cronograma' => $this->cronograma,
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    public function destinatariosLogicos(): array
    {
        $emails = [];
        $prestador = $this->cronograma->prestador;
        if ($prestador?->email) {
            $emails[] = $prestador->email;
        }

        // Em produção/staging, copia o canal TDAP corporativo.
        // Em local/test, redireciona para inbox de teste (mailhog).
        if (app()->environment(['local', 'testing'])) {
            $emails = ['tdap-test@local.sdc.mg.gov.br'];
        } else {
            $emails[] = 'tdap@ca.mg.gov.br';
        }

        return array_values(array_unique(array_filter($emails)));
    }
}
