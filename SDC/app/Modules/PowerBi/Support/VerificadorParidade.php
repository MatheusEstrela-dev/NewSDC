<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Modules\PowerBi\Contracts\Materializador;
use App\Support\Database\ConexaoPowerBi;

/**
 * Paridade ao vivo x materializado: compara o hash de cada linha do service
 * de export (linhasAoVivo) com o hash gravado. Guarda um mapa chave => hash em
 * memoria por tabela (ok para as tabelas de BI; nao use em carga gigante).
 */
final class VerificadorParidade
{
    public function __construct(private readonly GravadorBi $gravador)
    {
    }

    /**
     * @return array<string, array{ao_vivo: int, materializado: int, divergentes: int, faltantes: int, sobrando: int}>
     */
    public function verificar(Materializador $m, ?string $so = null): array
    {
        JanelaDaFonte::obter(); // trava de standby tambem vale para o ao vivo

        $saida = [];
        foreach ($m->tabelas() as $nome => $d) {
            if ($so !== null && $so !== $nome) {
                continue;
            }
            $saida[$nome] = $this->tabela($m, $d);
        }

        return $saida;
    }

    /** @return array{ao_vivo: int, materializado: int, divergentes: int, faltantes: int, sobrando: int} */
    private function tabela(Materializador $m, DefinicaoTabela $d): array
    {
        $vivo = [];
        foreach ($m->linhasAoVivo($d->nome) as $linha) {
            $vivo[$d->chaveDe($linha)] = $this->gravador->hash($d, $linha);
        }

        $divergentes = $sobrando = $materializado = 0;
        $pk = implode(', ', $d->pk);
        $consulta = ConexaoPowerBi::escrita()->table($d->alvo())
            ->selectRaw("{$pk}, encode(hash, 'hex') AS h")->where('removido', false);

        foreach ($consulta->cursor() as $linha) {
            $materializado++;
            $chave = $d->chaveDe((array) $linha);
            if (! isset($vivo[$chave])) {
                $sobrando++;
            } elseif ($vivo[$chave] !== $linha->h) {
                $divergentes++;
            }
            unset($vivo[$chave]);
        }

        return [
            'ao_vivo' => $materializado - $sobrando + count($vivo),
            'materializado' => $materializado,
            'divergentes' => $divergentes,
            'faltantes' => count($vivo),
            'sobrando' => $sobrando,
        ];
    }
}
