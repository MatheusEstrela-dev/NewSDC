<?php

declare(strict_types=1);

namespace App\Modules\Rat\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Vistoria da ocorrencia aceita por um validador.
 *
 * ATENCAO — contrato declarado, ainda SEM emissor.
 *
 * O RAT de hoje nao tem fluxo de validacao de vistoria: `rat_relato_vistoria`
 * e gravada por updateOrCreate em RatWriteService::saveVistoria (mesmo caminho
 * do rascunho) e nenhuma migration do modulo cria coluna de validacao,
 * validador ou data de aceite. Salvar a vistoria e preencher formulario, nao
 * marco premiavel — emitir aqui seria inventar um aceite que ninguem deu.
 *
 * A classe existe porque o catalogo do ranking ja publica a regra
 * `rat.vistoria_validada` e o RatAdapter precisa saber traduzi-la assim que o
 * fluxo real existir. Quem implementar a validacao deve emitir este evento
 * dentro da transacao do aceite, com `validador_user_id` explicito.
 */
final readonly class VistoriaValidadaV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'rat.vistoria.validada';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'ocorrencia_id'      => $this->metadata['ocorrencia_id']      ?? null,
            'vistoria_id'        => $this->metadata['vistoria_id']        ?? null,
            'numero_bos'         => $this->metadata['numero_bos']         ?? null,
            'sequencial_ano'     => $this->metadata['sequencial_ano']     ?? null,
            'actor_user_id'      => $this->metadata['actor_user_id']      ?? null,
            'validador_user_id'  => $this->metadata['validador_user_id']  ?? null,
            'prazo_edicao'       => $this->metadata['prazo_edicao']       ?? null,
            'validada_em'        => $this->metadata['validada_em']        ?? null,
        ];
    }
}
