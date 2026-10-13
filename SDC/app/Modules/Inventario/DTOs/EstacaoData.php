<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/** Cadastro/edicao de estacao de trabalho (EstacaoRequest). */
final readonly class EstacaoData
{
    use SafeCastingTrait;

    public function __construct(
        public string $nome,
        public ?string $pontoRede = null,
        public ?int $userId = null,
    ) {}

    /** @param array<string, mixed> $dados validated() */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::asString($dados['nome'] ?? null),
            pontoRede: self::asStringOrNull($dados['ponto_rede'] ?? null),
            userId: self::asIntOrNull($dados['user_id'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'ponto_rede' => $this->pontoRede,
            'user_id' => $this->userId,
        ];
    }
}
