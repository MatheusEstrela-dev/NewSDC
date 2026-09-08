<?php

declare(strict_types=1);

namespace App\Modules\Cedec\DTOs;

use Illuminate\Http\Request;

/**
 * Filtro da listagem estadual de prefeituras. Imutavel: uma instancia por requisicao,
 * construida em fromRequest() e repassada inteira ao service.
 */
final class PrefeituraFiltroDTO
{
    private const PENDENCIAS_VALIDAS = ['sem_email', 'sem_telefone', 'sem_foto'];

    private const PER_PAGE_VALIDOS = [20, 50, 100];

    public function __construct(
        public ?string $busca = null,
        public ?int $redecId = null,
        public ?string $macrorregiao = null,
        public ?string $pendencia = null,
        public int $perPage = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $pendencia = $request->query('pendencia');
        $perPage = (int) $request->query('per_page', 20);

        return new self(
            busca: self::stringOuNulo($request->query('busca')),
            redecId: self::intOuNulo($request->query('redec_id')),
            macrorregiao: self::stringOuNulo($request->query('macrorregiao')),
            pendencia: in_array($pendencia, self::PENDENCIAS_VALIDAS, true) ? $pendencia : null,
            perPage: in_array($perPage, self::PER_PAGE_VALIDOS, true) ? $perPage : 20,
        );
    }

    /**
     * per_page entra aqui junto dos filtros. A pagina reenvia este array inteiro ao
     * paginar; sem ele, quem escolheu 100 por pagina voltava ao default de 20 no
     * primeiro clique de proxima pagina e pulava as linhas 21 a 100.
     *
     * @return array{busca: ?string, redec_id: ?int, macrorregiao: ?string, pendencia: ?string, per_page: int}
     */
    public function toArray(): array
    {
        return [
            'busca' => $this->busca,
            'redec_id' => $this->redecId,
            'macrorregiao' => $this->macrorregiao,
            'pendencia' => $this->pendencia,
            'per_page' => $this->perPage,
        ];
    }

    private static function stringOuNulo(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

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
