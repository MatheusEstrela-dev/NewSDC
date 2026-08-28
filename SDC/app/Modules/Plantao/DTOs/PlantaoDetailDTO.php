<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\Plantao;

class PlantaoDetailDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $data,
        public readonly string $periodo_label,
        public readonly string $status_label,
        public readonly string $status_valor,
        public readonly string $plantonista_nome,
        public readonly ?string $plantonista_saida_nome,
        public readonly ?string $localizacao,
        public readonly ?string $observacoes,
        public readonly ?string $ocorrencias_destaque,
        public readonly ?string $encerrado_em,
        public readonly ?string $encerrado_por_nome,
        public readonly bool $encerrado_por_terceiro,
        public readonly ?string $aceito_em,
        public readonly ?string $aceito_por_nome,
        public readonly ?string $divergencia,
        public readonly bool $pode_editar,
        /** @var list<array<string,mixed>> */
        public readonly array $snapshots,
        /** @var list<array<string,mixed>> */
        public readonly array $movimentacoes,
    ) {
    }

    public static function fromModel(Plantao $plantao, bool $podeEditar): self
    {
        return new self(
            id: $plantao->id,
            data: $plantao->data?->format('d/m/Y') ?? '',
            periodo_label: $plantao->periodo?->labelCurto() ?? '',
            status_label: $plantao->status?->label() ?? '',
            status_valor: $plantao->status?->value ?? '',
            plantonista_nome: $plantao->plantonista_nome ?? '',
            plantonista_saida_nome: $plantao->plantonista_saida_nome,
            localizacao: $plantao->localizacao,
            observacoes: $plantao->observacoes,
            ocorrencias_destaque: $plantao->ocorrencias_destaque,
            encerrado_em: $plantao->encerrado_em?->format('d/m/Y H:i'),
            encerrado_por_nome: $plantao->encerradoPor?->name,
            // Quando difere do dono do turno, o encerramento foi por terceiro
            // (spec 4.3) e a tela precisa deixar isso visivel.
            encerrado_por_terceiro: $plantao->encerrado_por_id !== null
                && (int) $plantao->encerrado_por_id !== (int) $plantao->plantonista_id,
            aceito_em: $plantao->aceito_em?->format('d/m/Y H:i'),
            aceito_por_nome: $plantao->aceitoPor?->name,
            divergencia: $plantao->divergencia,
            pode_editar: $podeEditar,
            snapshots: SnapshotDTO::collection($plantao->snapshots),
            movimentacoes: MovimentacaoDTO::collection($plantao->movimentacoes),
        );
    }
}
