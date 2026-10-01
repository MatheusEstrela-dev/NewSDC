<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

final readonly class PessoaRemanejadaData
{
    /** @param list<int> $equipamentoIds */
    public function __construct(
        public int $usuarioId,
        public ?int $estacaoOrigemId,
        public ?int $estacaoDestinoId,
        public ?string $condicaoDestino,
        public array $equipamentoIds,
    ) {}

    public static function fromArray(array $dados): self
    {
        $inteiro = static fn (mixed $valor): ?int => $valor === null || $valor === '' ? null : (int) $valor;
        $condicao = trim((string) ($dados['condicao_destino'] ?? ''));

        return new self(
            usuarioId: (int) $dados['usuario_id'],
            estacaoOrigemId: $inteiro($dados['estacao_origem_id'] ?? null),
            estacaoDestinoId: $inteiro($dados['estacao_destino_id'] ?? null),
            condicaoDestino: $condicao === '' ? null : mb_substr($condicao, 0, 60),
            // Sem array_unique: repeticao e erro de dominio e o service precisa ve-la.
            equipamentoIds: array_values(array_map('intval', (array) ($dados['equipamento_ids'] ?? []))),
        );
    }
}
