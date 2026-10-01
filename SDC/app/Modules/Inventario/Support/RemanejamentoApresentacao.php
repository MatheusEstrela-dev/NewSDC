<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Support;

use App\Modules\Inventario\Enums\StatusMovimentacao;
use App\Modules\Inventario\Enums\TipoMovimentacao;
use App\Modules\Inventario\Models\Movimentacao;
use App\Modules\Inventario\Models\Remanejamento;
use App\Modules\Inventario\Models\RemanejamentoPessoa;

/** Formato do lote para as telas. Sem consulta: espera as relacoes carregadas. */
final class RemanejamentoApresentacao
{
    public function paraListagem(Remanejamento $lote): array
    {
        return [
            'id' => $lote->id,
            'criado_em' => $lote->created_at?->toIso8601String(),
            'status' => $lote->status->value,
            'status_label' => $lote->status->label(),
            'observacao' => $lote->observacao,
            'registrado_por' => $lote->registradoPor?->name,
            'pessoas' => $lote->pessoas->map(fn (RemanejamentoPessoa $p): string => $this->nomeCurto($p->usuario?->name))->values()->all(),
            'itens_movidos' => $lote->itensRemanejados->count(),
            'itens' => $lote->itensRemanejados->map(static fn (Movimentacao $m): array => [
                'id' => $m->id,
                'equipamento' => $m->equipamento?->nome ?? '—',
                'patrimonio' => $m->equipamento?->patrimonio ?? '—',
                'usuario_destino' => $m->usuarioDestino?->name ?? '—',
                'estacao_destino' => $m->estacaoDestino?->nome ?? '—',
                'status' => $m->status,
                'status_label' => StatusMovimentacao::rotulo($m->status),
            ])->values()->all(),
            'demanda' => $lote->demanda !== null
                ? ['id' => $lote->demanda->id, 'protocolo' => $lote->demanda->protocolo]
                : null,
            'seplag' => [
                'envios' => $lote->seplag_envios,
                'ultimo_envio_em' => $lote->seplag_enviado_em?->toIso8601String(),
            ],
        ];
    }

    public function paraFormulario(Remanejamento $lote): array
    {
        $lote->loadMissing('pessoas.movimentacoes');

        return [
            'id' => $lote->id,
            'observacao' => $lote->observacao ?? '',
            'pessoas' => $lote->pessoas->sortBy('id')->map(static fn (RemanejamentoPessoa $p): array => [
                'usuario_id' => $p->usuario_id,
                'estacao_origem_id' => $p->estacao_origem_id,
                'estacao_destino_id' => $p->estacao_destino_id,
                'condicao_destino' => $p->condicao_destino ?? '',
                'equipamento_ids' => $p->movimentacoes
                    ->where('tipo', TipoMovimentacao::REMANEJAMENTO->value)
                    ->sortBy('id')->pluck('equipamento_id')
                    ->map(static fn ($id): int => (int) $id)->values()->all(),
            ])->values()->all(),
        ];
    }

    /** "Maria da Silva Souza" -> "Maria Souza". */
    public function nomeCurto(?string $nome): string
    {
        $partes = preg_split('/\s+/', trim((string) $nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($partes === []) {
            return '—';
        }

        return count($partes) === 1 ? $partes[0] : $partes[0].' '.$partes[count($partes) - 1];
    }
}
