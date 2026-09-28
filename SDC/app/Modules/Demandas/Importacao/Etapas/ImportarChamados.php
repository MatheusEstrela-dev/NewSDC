<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\PrioridadeSimples;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Enums\TipoDemanda;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\ResolvedorUsuarioLegado;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Carbon\CarbonImmutable;

/**
 * Grava direto no model, sem DemandaWriteService nem workflow: a carga preserva
 * datas e estado finais do legado, sem simular transicoes nem disparar eventos.
 */
final class ImportarChamados extends EtapaBase
{
    public function __construct(
        MapaImportacao $mapa,
        private readonly ResolvedorUsuarioLegado $usuarios,
        private readonly HistoricoDemanda $historico,
    ) {
        parent::__construct($mapa);
    }

    public function nome(): string { return 'chamados'; }
    protected function tabelaOrigem(): string { return 'chamados'; }
    protected function tabelaDestino(): string { return 'tasks'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $solicitante = $this->usuarios->resolver((int) $linha->solicitante_id);
        if ($solicitante === null) {
            return 'solicitante_nao_mapeado';
        }
        $criador = $linha->criador_id !== null ? $this->usuarios->resolver((int) $linha->criador_id) : $solicitante;
        if ($criador === null) {
            return 'criador_nao_mapeado';
        }
        $responsavel = $linha->destinatario_id !== null ? $this->usuarios->resolver((int) $linha->destinatario_id) : null;
        if ($linha->destinatario_id !== null && $responsavel === null) {
            return 'destinatario_nao_mapeado';
        }

        $status = MapaLegado::status((string) $linha->status);
        $prioridade = PrioridadeSimples::tryFrom((string) $linha->prioridade) ?? PrioridadeSimples::MEDIA;
        $criadoEm = CarbonImmutable::parse($linha->data_hora ?? $linha->created_at);
        $fechadoEm = $status === StatusDemanda::RESOLVIDA
            ? CarbonImmutable::parse($linha->data_fechamento ?? $linha->updated_at)
            : null;
        $assuntoId = $linha->assunto_id !== null ? $this->mapa->alvo('assuntos', (string) $linha->assunto_id) : null;

        $demanda = $destinoExistente !== null ? Demanda::withTrashed()->find($destinoExistente) : null;
        $novo = $demanda === null;
        $demanda ??= new Demanda();

        $demanda->forceFill([
            'tipo' => TipoDemanda::SOLICITACAO,
            'titulo' => mb_substr((string) $linha->titulo, 0, 255),
            'descricao' => (string) $linha->mensagem,
            'status' => $status,
            'impacto' => $prioridade->impacto(),
            'urgencia' => $prioridade->urgencia(),
            'solicitante_id' => $solicitante,
            'criado_por_id' => $criador,
            'atribuido_para_id' => $responsavel ?? ($status === StatusDemanda::ABERTA ? null : $criador),
            'assunto_id' => $assuntoId,
            'campos_customizados' => array_merge(MapaLegado::json($linha->dados_adicionais ?? null), [
                '_legado' => array_filter([
                    'id' => (int) $linha->id,
                    'tipo' => $linha->tipo,
                    'grupo' => $linha->grupo,
                    'obs' => $linha->obs,
                    'encaminhado_para_id' => $linha->encaminhado_para_id !== null ? $this->usuarios->resolver((int) $linha->encaminhado_para_id) : null,
                ], static fn ($v) => $v !== null && $v !== ''),
            ]),
            'resolvido_em' => $fechadoEm,
            'tempo_total_resolucao' => $fechadoEm !== null ? (int) round(abs($criadoEm->diffInMinutes($fechadoEm))) : null,
        ]);

        if ($novo) {
            // Protocolo com o ano original: sequencia gerada pelo proprio model,
            // mas o prefixo de ano vem da abertura legada.
            $demanda->protocolo = sprintf('%s-%d-%s', TipoDemanda::SOLICITACAO->getProtocoloPrefix(), $criadoEm->year, 'L'.str_pad((string) $linha->id, 6, '0', STR_PAD_LEFT));
        }
        $demanda->created_at = $criadoEm;
        $demanda->updated_at = CarbonImmutable::parse($linha->updated_at ?? $criadoEm);
        $demanda->timestamps = false;
        $demanda->saveQuietly();
        $demanda->timestamps = true;

        if ($novo) {
            $this->historico->registrar(
                $demanda, $criador, AcaoHistoricoDemanda::IMPORTADA,
                'Chamado #'.$linha->id.' importado do cedec-demanda.', ['legado_id' => (int) $linha->id], em: $criadoEm,
            );
        }

        return (int) $demanda->id;
    }
}
