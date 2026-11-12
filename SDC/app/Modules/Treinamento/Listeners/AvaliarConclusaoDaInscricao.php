<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Listeners;

use App\Modules\Treinamento\Events\ItemDaTrilhaConcluido;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\ReavaliacaoDeConclusaoService;

/**
 * Avalia se a inscricao concluiu o curso a cada item concluido (spec 8.4):
 * congela concluido_em/aproveitamento na inscricao e enfileira a emissao do
 * certificado. Idempotente: a conclusao grava uma vez e o certificado e unico
 * por inscricao, entao o evento repetido nao duplica nada.
 *
 * Falha na avaliacao ou na emissao e reportada e engolida: o item ja foi
 * concluido na requisicao que disparou o evento. O comando
 * treinamento:avaliar-conclusoes recupera a conclusao e o certificado pendentes.
 *
 * Registrado em App\Providers\EventServiceProvider (o provider do modulo nao
 * registra listeners).
 */
final class AvaliarConclusaoDaInscricao
{
    public function __construct(private readonly ReavaliacaoDeConclusaoService $reavaliacao) {}

    public function handle(ItemDaTrilhaConcluido $evento): void
    {
        $inscricao = Inscricao::query()->with('treinamento')->find($evento->inscricaoId);

        if ($inscricao !== null) {
            $this->reavaliacao->avaliarSemDerrubar($inscricao);
        }
    }
}
