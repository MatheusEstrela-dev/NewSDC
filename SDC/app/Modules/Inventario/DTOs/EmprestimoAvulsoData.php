<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Emprestimo avulso de equipamento (MovimentacaoController::store). O
 * remanejamento em lote tem contrato proprio (RemanejamentoData).
 *
 * quantidade vem como int: o Service compara com a quantidade do equipamento
 * em modo estrito, e o "3" de um envio por formulario nunca era igual a 3.
 */
final readonly class EmprestimoAvulsoData
{
    use SafeCastingTrait;

    public function __construct(
        public int $equipamentoId,
        public string $tipo,
        public int $quantidade,
        public ?int $usuarioDestinoId = null,
        public ?int $estacaoDestinoId = null,
        public ?string $dataPrevistaDevolucao = null,
        public ?string $observacao = null,
    ) {}

    /** @param array<string, mixed> $dados dados validados pelo Controller */
    public static function fromArray(array $dados): self
    {
        return new self(
            equipamentoId: self::asInt($dados['equipamento_id'] ?? null),
            tipo: self::asString($dados['tipo'] ?? null),
            quantidade: self::asInt($dados['quantidade'] ?? null),
            usuarioDestinoId: self::asIntOrNull($dados['usuario_destino_id'] ?? null),
            estacaoDestinoId: self::asIntOrNull($dados['estacao_destino_id'] ?? null),
            dataPrevistaDevolucao: self::asStringOrNull($dados['data_prevista_devolucao'] ?? null),
            observacao: self::asStringOrNull($dados['observacao'] ?? null),
        );
    }

    /** Colunas de inventario_ti_movimentacoes vindas do pedido. */
    public function toArray(): array
    {
        return [
            'equipamento_id' => $this->equipamentoId,
            'tipo' => $this->tipo,
            'quantidade' => $this->quantidade,
            'usuario_destino_id' => $this->usuarioDestinoId,
            'estacao_destino_id' => $this->estacaoDestinoId,
            'data_prevista_devolucao' => $this->dataPrevistaDevolucao,
            'observacao' => $this->observacao,
        ];
    }
}
