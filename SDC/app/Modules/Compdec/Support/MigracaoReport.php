<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Support;

/**
 * Sumario do ETL legado -> novo. Acumulado durante a migracao
 * e devolvido pelos *Service::migrarLegado() para a Command imprimir.
 */
class MigracaoReport
{
    public int $inseridos = 0;

    public int $atualizados = 0;

    public int $ignorados = 0;

    public int $erros = 0;

    /** @var array<int, array{legacy_id: int|null, motivo: string}> */
    public array $errosDetalhes = [];

    public bool $dryRun = false;

    public function __construct(
        public readonly string $recurso,
    ) {}

    public function registrarInsercao(): void
    {
        $this->inseridos++;
    }

    public function registrarAtualizacao(): void
    {
        $this->atualizados++;
    }

    public function registrarSkip(): void
    {
        $this->ignorados++;
    }

    public function registrarErro(?int $legacyId, string $motivo): void
    {
        $this->erros++;
        $this->errosDetalhes[] = ['legacy_id' => $legacyId, 'motivo' => $motivo];
    }

    public function total(): int
    {
        return $this->inseridos + $this->atualizados + $this->ignorados + $this->erros;
    }

    /** @return array<string, int|string|bool|array<int, array<string, mixed>>> */
    public function toArray(): array
    {
        return [
            'recurso' => $this->recurso,
            'dry_run' => $this->dryRun,
            'total' => $this->total(),
            'inseridos' => $this->inseridos,
            'atualizados' => $this->atualizados,
            'ignorados' => $this->ignorados,
            'erros' => $this->erros,
            'erros_detalhes' => $this->errosDetalhes,
        ];
    }
}
