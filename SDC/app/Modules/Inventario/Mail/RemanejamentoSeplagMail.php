<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Mail;

use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use App\Services\Mail\VueEmailRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Symfony\Component\Mime\Email;

/**
 * Pedido de desbloqueio de pontos de rede a SEPLAG, com a planilha do lote.
 * Carrega so o id (primitivo, como os demais mailables em fila): a planilha e
 * gerada na execucao do job, entao reenviar um lote editado manda o atual.
 */
class RemanejamentoSeplagMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $remanejamentoId) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: (string) config('inventario.seplag.assunto_email'),
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
        $lote = $this->lote();

        return new Content(htmlString: app(VueEmailRenderer::class)->render('RemanejamentoSeplag', [
            'dataLote' => $lote->created_at->format('d/m/Y H:i'),
            'totalItens' => (int) $lote->itens_remanejados_count,
            'pessoas' => $lote->pessoas->map(static fn ($p): ?string => $p->usuario?->name)->filter()->values()->all(),
        ]));
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $planilha = app(PlanilhaRemanejamento::class);
        $lote = $this->lote();

        return [
            Attachment::fromData(static fn (): string => $planilha->gerar($lote), $planilha->nomeArquivo($lote))
                ->withMime(PlanilhaRemanejamento::MIME),
        ];
    }

    private function lote(): Remanejamento
    {
        return Remanejamento::query()
            ->with('pessoas.usuario:id,name')
            ->withCount('itensRemanejados')
            ->findOrFail($this->remanejamentoId);
    }
}
