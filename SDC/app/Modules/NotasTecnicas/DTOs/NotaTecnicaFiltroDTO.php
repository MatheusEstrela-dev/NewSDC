<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\DTOs;

use App\Modules\NotasTecnicas\Enums\NotaTecnicaStatus;

/**
 * Filtro do workspace de notas tecnicas. Imutavel: uma instancia por requisicao,
 * construida em fromArray() com a query string e repassada inteira ao service.
 *
 * Sem `status`, a lista mostra as notas em vigor (e os rascunhos, para quem
 * edita); revogadas so aparecem quando pedidas, como a pasta de obsoletos do
 * Notes.
 */
final readonly class NotaTecnicaFiltroDTO
{
    private const PER_PAGE_VALIDOS = [25, 50, 100];

    public const COLECAO_FAVORITAS = 'favoritas';

    public const COLECAO_RECENTES = 'recentes';

    public function __construct(
        public ?string $busca = null,
        public ?int $categoriaId = null,
        public ?NotaTecnicaStatus $status = null,
        public bool $naoLidas = false,
        public int $perPage = 25,
        public ?string $colecao = null,
    ) {}

    /**
     * @param  array<string, mixed>  $query  query string do workspace
     */
    public static function fromArray(array $query): self
    {
        $perPage = (int) ($query['per_page'] ?? 25);

        return new self(
            busca: self::stringOuNulo($query['busca'] ?? null),
            categoriaId: self::intOuNulo($query['categoria_id'] ?? null),
            status: NotaTecnicaStatus::tryFrom((string) ($query['status'] ?? '')),
            naoLidas: filter_var($query['nao_lidas'] ?? false, FILTER_VALIDATE_BOOLEAN),
            perPage: in_array($perPage, self::PER_PAGE_VALIDOS, true) ? $perPage : 25,
            // Fora da lista vira null: colecao e um filtro fechado.
            colecao: in_array($query['colecao'] ?? null, [self::COLECAO_FAVORITAS, self::COLECAO_RECENTES], true)
                ? $query['colecao']
                : null,
        );
    }

    /**
     * @return array{busca: ?string, categoria_id: ?int, status: ?string, nao_lidas: bool, per_page: int, colecao: ?string}
     */
    public function toArray(): array
    {
        return [
            'busca' => $this->busca,
            'categoria_id' => $this->categoriaId,
            'status' => $this->status?->value,
            'nao_lidas' => $this->naoLidas,
            'per_page' => $this->perPage,
            'colecao' => $this->colecao,
        ];
    }

    private static function stringOuNulo(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        // UTF-8 invalido na URL (ex.: %E7 em latin1) faria o Postgres recusar a
        // consulta com erro 500. mb_scrub troca o byte ruim por "?".
        $texto = trim(mb_scrub((string) $valor, 'UTF-8'));

        return $texto === '' ? null : $texto;
    }

    private static function intOuNulo(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return is_numeric($valor) ? (int) $valor : null;
    }
}
