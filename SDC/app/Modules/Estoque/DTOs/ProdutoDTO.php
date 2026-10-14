<?php

declare(strict_types=1);

namespace App\Modules\Estoque\DTOs;

use App\Modules\Shared\DTOs\Traits\LeituraComPresenca;
use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Cadastro de produto do estoque. Contrato de update do projeto: ausente
 * preserva, null/'' limpa, false/0 grava; default de criacao vem do banco.
 */
final readonly class ProdutoDTO
{
    use LeituraComPresenca;
    use SafeCastingTrait;

    /** @param list<string> $presentes */
    public function __construct(
        public ?string $sku,
        public ?string $nome,
        public ?string $categoria,
        public ?string $unidadeBase,
        public ?string $patrimonio,
        public ?int $estoqueMinimo,
        public ?bool $controlaValidade,
        public ?bool $ativo,
        public array $presentes = [],
    ) {}

    public static function fromValidated(array $dados): self
    {
        $p = [];

        return new self(
            sku:              self::asStringOrNull(self::ler($dados, $p, 'sku')),
            nome:             self::asStringOrNull(self::ler($dados, $p, 'nome')),
            categoria:        self::asStringOrNull(self::ler($dados, $p, 'categoria')),
            unidadeBase:      self::asStringOrNull(self::ler($dados, $p, 'unidade_base')),
            patrimonio:       self::asStringOrNull(self::ler($dados, $p, 'patrimonio')),
            estoqueMinimo:    self::asIntOrNull(self::ler($dados, $p, 'estoque_minimo')),
            controlaValidade: self::asBoolOrNull(self::ler($dados, $p, 'controla_validade')),
            ativo:            self::asBoolOrNull(self::ler($dados, $p, 'ativo')),
            presentes:        $p,
        );
    }

    public function toArray(): array
    {
        return self::somentePresentes([
            'sku'               => $this->sku !== null ? mb_strtoupper($this->sku) : null,
            'nome'              => $this->nome,
            'categoria'         => $this->categoria,
            'unidade_base'      => $this->unidadeBase,
            'patrimonio'        => $this->patrimonio,
            'estoque_minimo'    => $this->estoqueMinimo,
            'controla_validade' => $this->controlaValidade,
            'ativo'             => $this->ativo,
        ], $this->presentes);
    }
}
