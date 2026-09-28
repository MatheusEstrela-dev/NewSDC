<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Demandas\Domain\Events\DemandaResolvidaV1;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;

/**
 * Demanda resolvida = entrega aceita, para quem resolveu. Mover card, comentar e
 * transferir valem zero e nem chegam aqui. Reabrir e resolver de novo produz a
 * mesma chave canonica, entao o livro segura o premio duplo.
 */
final class DemandaAdapter implements ModuleAdapter
{
    public function modulo(): string { return 'Demandas'; }

    public function ruleKeys(): array { return ['demandas.entrega_aceita']; }

    public function suporta(DomainEvent $evento): bool
    {
        return $evento instanceof DemandaResolvidaV1;
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        if (! $evento instanceof DemandaResolvidaV1) {
            return null;
        }
        $ator = $evento->resolvidoPorId > 0 ? $evento->resolvidoPorId : null;
        $responsavel = $evento->responsavelId !== null && $evento->responsavelId > 0 ? $evento->responsavelId : null;

        // O credito vai para o responsavel atribuido (atribuido_para_id) no
        // momento da resolucao, nao para quem clicou em resolver -- um gestor
        // pode resolver em nome de outra pessoa. Uma demanda resolvida sempre
        // tem responsavel porque ExigeAtribuicaoParaProgresso exige atribuicao
        // para a demanda chegar em em_progresso; sem ele (dado legado ou
        // anomalia) nao ha o que comprovar, entao evidencia acompanha autoria.
        return new FatoNormalizado(
            eventId: $evento->eventId, eventName: $evento->eventName(), modulo: 'Demandas',
            chaveCanonica: 'demanda:'.$evento->aggregateId.':entrega_aceita', familia: 'demandas_entrega_aceita',
            ruleKey: 'demandas.entrega_aceita', ocorridoEm: $evento->occurredAt, competenciaEm: $evento->occurredAt,
            autoriaComprovada: $responsavel !== null, evidenciaComprovada: $responsavel !== null, validada: false,
            actorUserId: $ator, creditedUserId: $responsavel, entregueEm: $evento->occurredAt,
            contexto: ['demanda_id' => $evento->aggregateId, 'fonte' => 'demanda.resolvida'],
        );
    }
}
