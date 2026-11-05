<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Support;

use App\Support\Database\ConexaoGeo;
use App\Support\Database\Geo\DominioGeo;
use Throwable;

/**
 * Diario da coleta em <dominio>_ingestao.execucoes: quando cada fonte rodou,
 * como terminou e quanto gravou. Responde "o coletor esta vivo?" sem depender
 * do payload bruto, que o rollup Parquet poda.
 *
 * Os nomes de coluna e de status ficam so aqui: o schema e do banco (ver
 * EsquemaGeo::ingestao) e esta classe e o unico ponto de codigo que o conhece.
 */
final class ExecucaoDeColeta
{
    public const EXECUTANDO = 'executando';

    public const SUCESSO = 'sucesso';

    public const FALHA = 'falha';

    public const SEM_MUDANCA = 'sem_mudanca';

    public function iniciar(DominioGeo $dominio, string $fonte): int
    {
        return (int) ConexaoGeo::conexao()
            ->table(TabelasGeo::execucoes($dominio))
            ->insertGetId([
                'fonte' => $fonte,
                'inicio' => now(),
                'status' => self::EXECUTANDO,
            ]);
    }

    public function encerrar(
        DominioGeo $dominio,
        ?int $execucaoId,
        string $status,
        ?int $registros = null,
        ?string $hash = null,
        ?Throwable $erro = null,
        ?string $mensagem = null,
    ): void {
        if ($execucaoId === null) {
            return;
        }

        $texto = $mensagem ?? $erro?->getMessage();

        ConexaoGeo::conexao()
            ->table(TabelasGeo::execucoes($dominio))
            ->where('id', $execucaoId)
            ->update([
                'fim' => now(),
                'status' => $status,
                'registros' => $registros,
                'hash' => $hash,
                'erro' => $texto !== null ? mb_substr($texto, 0, 2000) : null,
            ]);
    }
}
