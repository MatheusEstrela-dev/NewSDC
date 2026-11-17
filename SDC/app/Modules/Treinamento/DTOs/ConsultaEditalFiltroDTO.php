<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

/**
 * Filtros da consulta para edital. Imutavel; valor invalido vira "sem filtro"
 * (curso invalido sai da lista, modo desconhecido volta ao padrao).
 */
final readonly class ConsultaEditalFiltroDTO
{
    public const MODO_TODOS = 'todos';

    public const MODO_QUALQUER = 'qualquer';

    /**
     * @param  list<int>  $cursos  ids do catalogo
     */
    public function __construct(
        public array $cursos = [],
        public string $modo = self::MODO_TODOS,
        public ?int $redecId = null,
        public ?int $municipioId = null,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromArray(array $query): self
    {
        $cursos = is_array($query['cursos'] ?? null) ? $query['cursos'] : [];

        return new self(
            cursos: array_values(array_unique(array_filter(
                array_map(fn ($v) => self::inteiroPositivo($v), $cursos),
                fn (?int $v) => $v !== null,
            ))),
            modo: ($query['modo'] ?? null) === self::MODO_QUALQUER ? self::MODO_QUALQUER : self::MODO_TODOS,
            redecId: self::inteiroPositivo($query['redec_id'] ?? null),
            municipioId: self::inteiroPositivo($query['municipio_id'] ?? null),
        );
    }

    public function temCursos(): bool
    {
        return $this->cursos !== [];
    }

    /** @return array{cursos: list<int>, modo: string, redec_id: ?int, municipio_id: ?int} */
    public function toArray(): array
    {
        return [
            'cursos' => $this->cursos,
            'modo' => $this->modo,
            'redec_id' => $this->redecId,
            'municipio_id' => $this->municipioId,
        ];
    }

    private static function inteiroPositivo(mixed $valor): ?int
    {
        if (! is_scalar($valor) || ! preg_match('/^\d+$/', (string) $valor)) {
            return null;
        }

        return (int) $valor > 0 ? (int) $valor : null;
    }
}
