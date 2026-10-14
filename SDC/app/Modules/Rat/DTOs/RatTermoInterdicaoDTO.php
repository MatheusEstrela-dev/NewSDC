<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Shared\DTOs\Traits\LeituraComPresenca;
use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Entrada do termo de interdicao (#57). Contrato de update do projeto:
 * campo ausente preserva, null/'' limpa.
 */
final readonly class RatTermoInterdicaoDTO
{
    use SafeCastingTrait;
    use LeituraComPresenca;

    /** @param list<string> $presentes */
    public function __construct(
        public ?string $tipo,
        public ?string $areaInterditada,
        public ?string $fundamentacao,
        public ?string $medidas,
        public ?int $prazoDias,
        public ?string $responsavelNome,
        public ?string $registroCrea,
        public array $presentes = [],
    ) {}

    public static function fromValidated(array $dados): self
    {
        $p = [];

        return new self(
            tipo:            self::asStringOrNull(self::ler($dados, $p, 'tipo')),
            areaInterditada: self::asStringOrNull(self::ler($dados, $p, 'area_interditada')),
            fundamentacao:   self::asStringOrNull(self::ler($dados, $p, 'fundamentacao')),
            medidas:         self::asStringOrNull(self::ler($dados, $p, 'medidas')),
            prazoDias:       self::asIntOrNull(self::ler($dados, $p, 'prazo_dias')),
            responsavelNome: self::asStringOrNull(self::ler($dados, $p, 'responsavel_nome')),
            registroCrea:    self::asStringOrNull(self::ler($dados, $p, 'registro_crea')),
            presentes:       $p,
        );
    }

    /** Somente as colunas que vieram na requisicao. */
    public function toArray(): array
    {
        return self::somentePresentes([
            'tipo'             => $this->tipo,
            'area_interditada' => $this->areaInterditada,
            'fundamentacao'    => $this->fundamentacao,
            'medidas'          => $this->medidas,
            'prazo_dias'       => $this->prazoDias,
            'responsavel_nome' => $this->responsavelNome,
            'registro_crea'    => $this->registroCrea,
        ], $this->presentes);
    }
}
