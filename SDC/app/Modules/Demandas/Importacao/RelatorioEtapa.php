<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao;

final class RelatorioEtapa
{
    public int $lidos = 0;
    public int $importados = 0;
    public int $atualizados = 0;
    public int $ignorados = 0;
    public int $rejeitados = 0;

    /** @var array<string, int> */
    public array $motivos = [];

    /**
     * Avisos que nao rejeitam a linha: ela e importada do mesmo jeito, mas o
     * operador precisa ver no relatorio que algo faltou (ex.: anexo do legado
     * sem o arquivo fisico correspondente).
     *
     * @var array<string, int>
     */
    public array $avisos = [];

    public function __construct(public readonly string $etapa) {}

    public function rejeitar(string $motivo): void
    {
        $this->rejeitados++;
        $this->motivos[$motivo] = ($this->motivos[$motivo] ?? 0) + 1;
    }

    public function avisar(string $motivo): void
    {
        $this->avisos[$motivo] = ($this->avisos[$motivo] ?? 0) + 1;
    }

    public function toArray(): array
    {
        return [$this->etapa, $this->lidos, $this->importados, $this->atualizados, $this->ignorados, $this->rejeitados];
    }
}
