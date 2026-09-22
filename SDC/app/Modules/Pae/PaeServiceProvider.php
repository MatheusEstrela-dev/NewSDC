<?php

declare(strict_types=1);

namespace App\Modules\Pae;

use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Pae\Domain\Events\FormularioValidadoV1;
use App\Modules\Pae\Domain\Events\ParecerConcluidoV1;
use App\Modules\Pae\Domain\Events\ProtocoloEnviadoV1;
use App\Modules\Pae\Domain\Events\RevisaoAceitaV1;
use App\Modules\Pae\Services\EmpreendimentoApiService;
use App\Modules\Pae\Services\PaeFormularioService;
use App\Modules\Pae\Services\PaeNotificacaoService;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Support\ServiceProvider;

/**
 * PAE - Plano de Acao de Emergencia.
 *
 * Provider criado junto com a instrumentacao de Domain Events: ate entao o
 * modulo funcionava sem provider, resolvendo os services por auto-wiring. Ele
 * passou a ser necessario porque OutboxDispatcher::reidratar() so sabe
 * reconstruir um evento cujo par nome@versao esteja registrado; sem o mapa
 * abaixo, persist() grava a linha e o worker falha ao despachar, deixando os
 * eventos presos em outbox_events.
 */
class PaeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaeProtocoloService::class);
        $this->app->singleton(PaeFormularioService::class);
        $this->app->singleton(PaeNotificacaoService::class);
        $this->app->singleton(EmpreendimentoApiService::class);

        $this->app->extend(OutboxDispatcher::class, function (OutboxDispatcher $dispatcher) {
            return $dispatcher
                ->register('pae.protocolo.enviado', 1, ProtocoloEnviadoV1::class)
                ->register('pae.formulario.validado', 1, FormularioValidadoV1::class)
                ->register('pae.revisao.aceita', 1, RevisaoAceitaV1::class)
                ->register('pae.parecer.concluido', 1, ParecerConcluidoV1::class);
        });
    }
}
