<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use App\Modules\Treinamento\Models\Prova;

/**
 * Onde uma inscricao esta numa prova. Leitura pura, montada pelo
 * SituacaoProvaService; quem grava tentativas e liberacoes sao os servicos
 * da prova.
 *
 * - tentativasPermitidas: max_tentativas da prova + soma das liberacoes.
 * - tentativasEnviadas: so as finalizadas contam (spec 8.6).
 * - notaFinal: maior nota entre as enviadas (spec 8.3); null sem envio.
 * - aprovada: alguma enviada gravada como aprovada (nao recalcula, spec 8.7).
 */
final readonly class SituacaoProva
{
    public function __construct(
        public int $provaId,
        public int $moduloItemId,
        public float $notaMinima,
        public int $totalQuestoes,
        public int $tentativasPermitidas,
        public int $tentativasEnviadas,
        public ?int $tentativaAbertaId,
        public ?float $notaFinal,
        public bool $aprovada,
    ) {}

    public function tentativasRestantes(): int
    {
        return max(0, $this->tentativasPermitidas - $this->tentativasEnviadas);
    }

    /** Esgotou as tentativas sem aprovar: so o professor destrava (spec 8.3). */
    public function travada(): bool
    {
        return ! $this->aprovada && $this->tentativaAbertaId === null && $this->tentativasRestantes() === 0;
    }

    /** Pode abrir uma tentativa nova (a aberta, se houver, e retomada); prova sem questao sorteavel nao abre. */
    public function podeIniciar(): bool
    {
        return ! $this->aprovada && $this->totalQuestoes > 0 && $this->tentativaAbertaId === null && $this->tentativasRestantes() > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'prova_id' => $this->provaId,
            'item_id' => $this->moduloItemId,
            'escala_nota' => Prova::ESCALA_NOTA,
            'nota_minima' => $this->notaMinima,
            'total_questoes' => $this->totalQuestoes,
            'tentativas_permitidas' => $this->tentativasPermitidas,
            'tentativas_enviadas' => $this->tentativasEnviadas,
            'tentativas_restantes' => $this->tentativasRestantes(),
            'tentativa_aberta_id' => $this->tentativaAbertaId,
            'nota_final' => $this->notaFinal,
            'aprovada' => $this->aprovada,
            'travada' => $this->travada(),
            'pode_iniciar' => $this->podeIniciar(),
            'em_preparacao' => $this->totalQuestoes === 0,
        ];
    }
}
