<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Domain\Estoque\MovimentoEstoque;
use App\Modules\AjudaHumanitaria\Domain\Estoque\SaldoInsuficiente;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Unico caminho de escrita no estoque.
 *
 * Grava o lancamento no ledger e reprojeta o saldo na MESMA transacao. Quem
 * garante que o saldo nao fica negativo e o CHECK do banco, nao um SELECT
 * previo: ler antes de escrever seria uma condicao de corrida sob Swoole, onde
 * varias requisicoes atendem o mesmo material em paralelo.
 *
 * O ON CONFLICT DO UPDATE toma lock da linha em ajuda_h_estoque_saldos, entao
 * duas transacoes concorrentes sobre o mesmo par material/deposito serializam
 * em vez de disputar.
 */
final class RegistrarMovimentoEstoque
{
    /** check_violation: o CHECK de saldo nao negativo barrou a projecao. */
    private const VIOLACAO_DE_CHECK = '23514';

    public function registrar(MovimentoEstoque $movimento): int
    {
        try {
            return DB::transaction(function () use ($movimento): int {
                $id = DB::table('ajuda_h_estoque_movimentos')->insertGetId([
                    'material_ah_id' => $movimento->materialAhId,
                    'deposito_id'    => $movimento->depositoId,
                    'quantidade'     => $movimento->quantidade,
                    'tipo'           => $movimento->tipo,
                    'origem_tipo'    => $movimento->origemTipo,
                    'origem_id'      => $movimento->origemId,
                    'ocorrido_em'    => $movimento->ocorridoEm ?? now(),
                    'registrado_por' => $movimento->registradoPor,
                    'created_at'     => now(),
                ]);

                // Duas instrucoes, e nao um ON CONFLICT DO UPDATE, por um motivo
                // que so aparece com movimento negativo: o ON CONFLICT resolve
                // apenas violacao de unicidade. O CHECK de saldo nao negativo e
                // avaliado antes, sobre a linha candidata do VALUES, entao uma
                // saida legitima de -30 sobre saldo 100 seria recusada ali,
                // sem nunca chegar ao UPDATE.
                //
                // Garantir a linha zerada primeiro sempre passa no CHECK, e o
                // UPDATE seguinte avalia a restricao sobre o saldo final. O
                // DO NOTHING cobre a criacao concorrente do mesmo par, e o
                // UPDATE toma lock da linha, serializando as transacoes que
                // disputam o mesmo material e deposito.
                DB::statement(
                    'INSERT INTO ajuda_h_estoque_saldos (material_ah_id, deposito_id, saldo, atualizado_em)
                     VALUES (?, ?, 0, now())
                     ON CONFLICT (material_ah_id, deposito_id) DO NOTHING',
                    [$movimento->materialAhId, $movimento->depositoId]
                );

                DB::statement(
                    'UPDATE ajuda_h_estoque_saldos
                        SET saldo = saldo + ?, atualizado_em = now()
                      WHERE material_ah_id = ? AND deposito_id = ?',
                    [$movimento->quantidade, $movimento->materialAhId, $movimento->depositoId]
                );

                return $id;
            });
        } catch (QueryException $erro) {
            if (($erro->errorInfo[0] ?? null) === self::VIOLACAO_DE_CHECK) {
                throw SaldoInsuficiente::para(
                    $movimento->materialAhId,
                    $movimento->depositoId,
                    $movimento->quantidade
                );
            }

            throw $erro;
        }
    }
}
