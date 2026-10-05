<?php

declare(strict_types=1);

namespace App\Support\Calendario;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Contagem de dias uteis: segunda a sexta, menos os feriados configurados.
 *
 * Compartilhada entre modulos. Os feriados vem de config/feriados.php (datas
 * fixas e moveis por ano), nao de tabela: a lista muda uma vez por ano e nao
 * justifica CRUD. Sem estado mutavel, entao pode ser singleton no Octane.
 */
final class CalendarioDiasUteis
{
    /** @var array<string, true> */
    private readonly array $feriados;

    /**
     * @param  iterable<string>  $feriados  Datas no formato Y-m-d
     */
    public function __construct(iterable $feriados = [])
    {
        $mapa = [];
        foreach ($feriados as $data) {
            $mapa[(string) $data] = true;
        }
        $this->feriados = $mapa;
    }

    public static function padrao(): self
    {
        $datas = [];
        foreach ((array) config('feriados.datas', []) as $doAno) {
            foreach (array_keys((array) $doAno) as $data) {
                $datas[] = (string) $data;
            }
        }

        return new self($datas);
    }

    public function ehDiaUtil(CarbonInterface $data): bool
    {
        return ! $data->isWeekend() && ! isset($this->feriados[$data->format('Y-m-d')]);
    }

    /**
     * Soma dias uteis a partir do dia seguinte ao inicio (o dia do evento nao conta).
     */
    public function adicionarDiasUteis(CarbonInterface $inicio, int $dias): CarbonImmutable
    {
        $data = CarbonImmutable::instance($inicio)->startOfDay();
        $contados = 0;

        while ($contados < $dias) {
            $data = $data->addDay();
            if ($this->ehDiaUtil($data)) {
                $contados++;
            }
        }

        return $data;
    }
}
