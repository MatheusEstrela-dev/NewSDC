<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

final readonly class RemanejamentoData
{
    /** @param list<PessoaRemanejadaData> $pessoas */
    public function __construct(
        public ?string $observacao,
        public array $pessoas,
        public int $autorId,
    ) {}

    public static function fromArray(array $dados, int $autorId): self
    {
        $observacao = trim((string) ($dados['observacao'] ?? ''));

        return new self(
            observacao: $observacao === '' ? null : $observacao,
            pessoas: array_values(array_map(
                static fn (array $pessoa): PessoaRemanejadaData => PessoaRemanejadaData::fromArray($pessoa),
                (array) ($dados['pessoas'] ?? []),
            )),
            autorId: $autorId,
        );
    }

    /** @return list<int> */
    public function usuarioIds(): array
    {
        return $this->unicos(array_map(static fn (PessoaRemanejadaData $p): int => $p->usuarioId, $this->pessoas));
    }

    /** @return list<int> */
    public function equipamentoIds(): array
    {
        return $this->unicos(array_merge([], ...array_map(
            static fn (PessoaRemanejadaData $p): array => $p->equipamentoIds,
            $this->pessoas,
        )));
    }

    /** @return list<int> */
    public function estacaoDestinoIds(): array
    {
        return $this->unicos(array_map(static fn (PessoaRemanejadaData $p): ?int => $p->estacaoDestinoId, $this->pessoas));
    }

    /** @return list<int> */
    public function estacaoIds(): array
    {
        return $this->unicos(array_merge(
            array_map(static fn (PessoaRemanejadaData $p): ?int => $p->estacaoOrigemId, $this->pessoas),
            $this->estacaoDestinoIds(),
        ));
    }

    /** @param list<int|null> $ids @return list<int> */
    private function unicos(array $ids): array
    {
        return array_values(array_unique(array_filter($ids, static fn (?int $id): bool => $id !== null)));
    }
}
