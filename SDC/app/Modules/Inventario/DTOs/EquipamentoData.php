<?php

declare(strict_types=1);

namespace App\Modules\Inventario\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Cadastro/edicao de equipamento (EquipamentoRequest). O formulario envia o
 * registro inteiro, entao null e campo apagado e e gravado.
 */
final readonly class EquipamentoData
{
    use SafeCastingTrait;

    public function __construct(
        public string $nome,
        public string $patrimonio,
        public bool $emprestavel,
        public int $quantidade,
        public ?string $numeroSerie = null,
        public ?string $ramal = null,
        public ?int $categoriaId = null,
        public ?string $unidade = null,
        public ?string $diretoria = null,
        public ?string $observacao = null,
    ) {}

    /** @param array<string, mixed> $dados validated() */
    public static function fromArray(array $dados): self
    {
        return new self(
            nome: self::asString($dados['nome'] ?? null),
            patrimonio: self::asString($dados['patrimonio'] ?? null),
            emprestavel: self::asBool($dados['emprestavel'] ?? null),
            quantidade: self::asInt($dados['quantidade'] ?? null, 1),
            numeroSerie: self::asStringOrNull($dados['numero_serie'] ?? null),
            ramal: self::asStringOrNull($dados['ramal'] ?? null),
            categoriaId: self::asIntOrNull($dados['categoria_id'] ?? null),
            unidade: self::asStringOrNull($dados['unidade'] ?? null),
            diretoria: self::asStringOrNull($dados['diretoria'] ?? null),
            observacao: self::asStringOrNull($dados['observacao'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'patrimonio' => $this->patrimonio,
            'numero_serie' => $this->numeroSerie,
            'ramal' => $this->ramal,
            'categoria_id' => $this->categoriaId,
            'unidade' => $this->unidade,
            'diretoria' => $this->diretoria,
            'emprestavel' => $this->emprestavel,
            'quantidade' => $this->quantidade,
            'observacao' => $this->observacao,
        ];
    }
}
