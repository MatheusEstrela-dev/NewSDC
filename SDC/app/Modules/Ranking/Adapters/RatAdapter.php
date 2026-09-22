<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Adapters;

use App\Core\Events\DomainEvent;
use App\Modules\Ranking\Contracts\ModuleAdapter;
use App\Modules\Ranking\DTOs\FatoNormalizado;
use DateTimeImmutable;
use Throwable;

/**
 * Traduz os eventos do RAT para o vocabulario do ranking.
 *
 * O conhecimento sujo que este adaptador carrega:
 *
 * 1. AUTORIA NAO E RECONSTRUIVEL A PARTIR DAS TABELAS. No RAT,
 *    `rat_ocorrencias.created_by`/`updated_by` sao string(191) e
 *    `rat_relato_recursos.created_by` e unsignedBigInteger — tipos diferentes
 *    para a mesma ideia, e NENHUMA das duas tem foreign key para `users` (so a
 *    tabela legada `rats`, ja removida, tinha). Por isso este adaptador le
 *    autoria EXCLUSIVAMENTE de `metadata.actor_user_id`, capturado com
 *    Auth::id() no momento da acao. Sem ele, autoriaComprovada = false e o
 *    fato vai para apuracao — nunca se deduz autor do texto de created_by nem
 *    do ultimo editor.
 *
 * 2. RASCUNHO NAO E ENTREGA. O RAT salva rascunho pelo mesmo caminho da
 *    gravacao definitiva (saveDraft/status 0). Evento marcado como rascunho,
 *    autosave, ou "finalizado" com status diferente de 1 devolve null.
 *
 * 3. O PROTOCOLO E A EVIDENCIA. `numero_bos` (YYYY-SEQUENCIAL[-SUFIXO]) e o
 *    identificador documental da ocorrencia; sem ele nao ha o que auditar e
 *    evidenciaComprovada = false.
 */
final class RatAdapter implements ModuleAdapter
{
    private const MODULO = 'Rat';

    /**
     * eventName => [marco (sufixo da chave canonica), rule_key, familia].
     *
     * rule_key e familia sao copia literal de RankingRegraSeeder::catalogo():
     * divergir de uma letra faz a regra vigente nao ser encontrada e o fato
     * cair em regra_nao_publicada.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const MARCOS = [
        'rat.ocorrencia.registrada' => ['registro_completo', 'rat.registro_completo', 'rat_registro_completo'],
        'rat.relatorio.finalizado' => ['relatorio_finalizado', 'rat.relatorio_finalizado', 'rat_relatorio_finalizado'],
        'rat.vistoria.validada' => ['vistoria_validada', 'rat.vistoria_validada', 'rat_vistoria_validada'],
    ];

    /** Chaves de metadata que datam o marco, por evento. */
    private const DATA_DO_MARCO = [
        'rat.ocorrencia.registrada' => 'registrado_em',
        'rat.relatorio.finalizado' => 'finalizado_em',
        'rat.vistoria.validada' => 'validada_em',
    ];

    public function modulo(): string
    {
        return self::MODULO;
    }

    public function suporta(DomainEvent $evento): bool
    {
        return isset(self::MARCOS[$evento->eventName()]);
    }

    public function normalizar(DomainEvent $evento): ?FatoNormalizado
    {
        $nome = $evento->eventName();

        if (! isset(self::MARCOS[$nome])) {
            return null;
        }

        $metadata = $evento->metadata;

        // Rascunho/autosave: reconhecido, porem nao premiavel.
        if ($this->eRascunho($metadata)) {
            return null;
        }

        // Finalizacao que nao fechou o relatorio nao e finalizacao. Status
        // ausente nao bloqueia (evento antigo); status presente e diferente de
        // 1 bloqueia.
        if ($nome === 'rat.relatorio.finalizado'
            && array_key_exists('status', $metadata)
            && (int) $metadata['status'] !== 1) {
            return null;
        }

        [$marco, $ruleKey, $familia] = self::MARCOS[$nome];

        $ocorrenciaId = $this->identificadorOcorrencia($evento);

        if ($ocorrenciaId === '') {
            return null;
        }

        $actorUserId = $this->idPositivo($metadata['actor_user_id'] ?? null);
        $autoria = $actorUserId !== null;

        // Validacao por terceiro so existe onde a origem nomeia o validador.
        $validador = $this->idPositivo($metadata['validador_user_id'] ?? null);
        if ($nome === 'rat.vistoria.validada' && $validador === null) {
            $validador = $actorUserId;
        }

        $protocolo = trim((string) ($metadata['numero_bos'] ?? ''));
        $entregueEm = $this->paraData($metadata[self::DATA_DO_MARCO[$nome]] ?? null);
        $prazo = $this->paraData($metadata['prazo_edicao'] ?? null);
        $ocorridoEm = $evento->occurredAt;

        return new FatoNormalizado(
            eventId: $evento->eventId,
            eventName: $nome,
            modulo: self::MODULO,
            chaveCanonica: sprintf('rat:ocorrencia:%s:%s', $ocorrenciaId, $marco),
            familia: $familia,
            ruleKey: $ruleKey,
            ocorridoEm: $ocorridoEm,
            // Entrega aceita com atraso pontua na competencia da entrega, nao
            // na data em que o evento foi despachado.
            competenciaEm: $entregueEm ?? $ocorridoEm,
            autoriaComprovada: $autoria,
            evidenciaComprovada: $protocolo !== '',
            validada: $validador !== null,
            actorUserId: $actorUserId,
            // Quem registra o RAT e quem recebe o credito; sem autoria
            // comprovada o fato segue para apuracao sem creditado.
            creditedUserId: $autoria ? $actorUserId : null,
            validadorUserId: $validador,
            prazoEfetivo: $prazo,
            entregueEm: $entregueEm,
            contexto: $this->contexto($metadata),
        );
    }

    /**
     * Identidade da ocorrencia. Preferir o metadata mantem a chave estavel
     * mesmo quando o agregado do envelope muda de granularidade (a vistoria,
     * por exemplo, e filha da ocorrencia).
     */
    private function identificadorOcorrencia(DomainEvent $evento): string
    {
        $doMetadata = trim((string) ($evento->metadata['ocorrencia_id'] ?? ''));

        return $doMetadata !== '' ? $doMetadata : trim($evento->aggregateId);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function eRascunho(array $metadata): bool
    {
        foreach (['rascunho', 'autosave', 'draft'] as $flag) {
            if (filter_var($metadata[$flag] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        $origem = mb_strtolower(trim((string) ($metadata['origem'] ?? '')));

        return in_array($origem, ['rascunho', 'draft', 'autosave'], true);
    }

    /**
     * Aceita int e string numerica (o metadata volta do JSONB do outbox).
     * Qualquer outra coisa — inclusive nome de usuario em texto livre, que e o
     * que created_by guarda no RAT — vira null.
     */
    private function idPositivo(mixed $valor): ?int
    {
        if ($valor === null || is_bool($valor)) {
            return null;
        }

        if (! is_int($valor) && ! (is_string($valor) && preg_match('/^[0-9]+$/', trim($valor)) === 1)) {
            return null;
        }

        $id = (int) $valor;

        return $id > 0 ? $id : null;
    }

    private function paraData(mixed $valor): ?DateTimeImmutable
    {
        if ($valor instanceof DateTimeImmutable) {
            return $valor;
        }

        if ($valor instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($valor);
        }

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable(trim($valor));
        } catch (Throwable) {
            // Data ilegivel e limitacao da fonte: nao bonifica e nao penaliza.
            return null;
        }
    }

    /**
     * Evidencia auxiliar para auditoria, sem dado pessoal desnecessario.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function contexto(array $metadata): array
    {
        $contexto = [
            'numero_bos' => $metadata['numero_bos'] ?? null,
            'sequencial_ano' => $metadata['sequencial_ano'] ?? null,
            'status' => $metadata['status'] ?? null,
            'origem' => $metadata['origem'] ?? null,
            'vistoria_id' => $metadata['vistoria_id'] ?? null,
        ];

        return array_filter($contexto, static fn ($v): bool => $v !== null && $v !== '');
    }
}
