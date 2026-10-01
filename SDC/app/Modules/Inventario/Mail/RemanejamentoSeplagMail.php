<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Mail;

use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Services\PlanilhaRemanejamento;
use App\Services\Mail\VueEmailRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;

/**
 * Pedido de desbloqueio de pontos de rede a SEPLAG, com a planilha do lote.
 * Carrega so o id (primitivo, como os demais mailables em fila): a planilha e
 * gerada na execucao do job, entao reenviar um lote editado manda o atual.
 */
class RemanejamentoSeplagMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /** Preenchido so na execucao (o job e serializado antes, com ele nulo). */
    private ?Remanejamento $lote = null;

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

    /**
     * O lote pode ter sido desfeito entre o enfileiramento e o worker: o pedido
     * de desbloqueio ja nao vale. Descarta sem excecao, senao o job tentaria de
     * novo a cada retry e o aviso viraria uma enxurrada.
     */
    public function send($mailer)
    {
        $lote = $this->carregarLote();
        if ($lote === null || ! $lote->estaAtivo()) {
            Log::warning('Envio a SEPLAG descartado: o lote foi desfeito ou removido antes do envio.', [
                'remanejamento_id' => $this->remanejamentoId,
            ]);

            return null;
        }

        return parent::send($mailer);
    }

    private function lote(): Remanejamento
    {
        return $this->carregarLote()
            ?? throw (new ModelNotFoundException())->setModel(Remanejamento::class, [$this->remanejamentoId]);
    }

    /** Uma leitura por envio: send, content e attachments usam o mesmo lote. */
    private function carregarLote(): ?Remanejamento
    {
        return $this->lote ??= Remanejamento::query()
            ->with('pessoas.usuario:id,name')
            ->withCount('itensRemanejados')
            ->find($this->remanejamentoId);
    }
}
