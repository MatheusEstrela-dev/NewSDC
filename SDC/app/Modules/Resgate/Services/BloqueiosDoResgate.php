<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Resgate\Enums\EscopoCarteira;
use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Support\Rastro;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Bloqueio judicial ou administrativo (premissa P7 do plano).
 *
 * Suspende na hora um ente inteiro ou um pedido. Enquanto vigente, nenhuma
 * transicao do pedido avanca (exigirLiberado) e o ente nao faz pedido novo.
 * Reservas ficam congeladas: o bloqueio nao libera nem debita nada sozinho,
 * porque a decisao que o originou e que diz o destino dos pontos.
 */
final class BloqueiosDoResgate
{
    public function bloquearEnte(EscopoCarteira $escopo, int $enteId, string $tipo, string $documento, string $motivo, int $autorId, Rastro $rastro): int
    {
        return $this->inserir(['alvo' => 'ente', 'ente_escopo' => $escopo->value, 'ente_id' => $enteId], $tipo, $documento, $motivo, $autorId, $rastro);
    }

    public function bloquearPedido(int $pedidoId, string $tipo, string $documento, string $motivo, int $autorId, Rastro $rastro): int
    {
        return $this->inserir(['alvo' => 'pedido', 'pedido_id' => $pedidoId], $tipo, $documento, $motivo, $autorId, $rastro);
    }

    public function encerrar(int $bloqueioId, string $motivo, int $autorId): void
    {
        $atualizados = $this->conexao()->update(
            'UPDATE resgate.bloqueios SET encerrado_por = ?, encerrado_em = now(), encerramento_motivo = ?
              WHERE id = ? AND encerrado_em IS NULL',
            [$autorId, $motivo, $bloqueioId],
        );
        if ($atualizados === 0) {
            throw new RegraDoResgate('Bloqueio não encontrado ou já encerrado.', 'bloqueio');
        }
    }

    /**
     * Bloqueio vigente que alcanca o pedido (pelo proprio pedido ou pelo ente).
     * Recebe a conexao da transacao em curso para ler sob o mesmo snapshot.
     */
    public function vigentePara(Connection $db, object $pedido): ?object
    {
        return $db->selectOne(
            "SELECT * FROM resgate.bloqueios
              WHERE encerrado_em IS NULL
                AND (pedido_id = ? OR (ente_escopo = ? AND ente_id = ?))
              ORDER BY registrado_em LIMIT 1",
            [(int) $pedido->id, (string) $pedido->ente_escopo, (int) $pedido->ente_id],
        );
    }

    public function vigenteParaEnte(Connection $db, EscopoCarteira $escopo, int $enteId): ?object
    {
        return $db->selectOne(
            'SELECT * FROM resgate.bloqueios WHERE encerrado_em IS NULL AND ente_escopo = ? AND ente_id = ? ORDER BY registrado_em LIMIT 1',
            [$escopo->value, $enteId],
        );
    }

    /** Recusa a transicao enquanto houver bloqueio vigente sobre o pedido. */
    public function exigirLiberado(Connection $db, object $pedido): void
    {
        $bloqueio = $this->vigentePara($db, $pedido);
        if ($bloqueio !== null) {
            throw new RegraDoResgate(
                "Pedido suspenso por bloqueio {$bloqueio->tipo} ({$bloqueio->documento_origem}). Nenhuma etapa avança até o encerramento do bloqueio.",
                'pedido',
            );
        }
    }

    /** @return list<array<string, mixed>> */
    public function listar(?int $pedidoId = null, ?EscopoCarteira $escopo = null, ?int $enteId = null): array
    {
        $onde = [];
        $valores = [];
        if ($pedidoId !== null) {
            $onde[] = 'pedido_id = ?';
            $valores[] = $pedidoId;
        }
        if ($escopo !== null && $enteId !== null) {
            $onde[] = '(ente_escopo = ? AND ente_id = ?)';
            array_push($valores, $escopo->value, $enteId);
        }
        $filtro = $onde === [] ? '' : 'WHERE ' . implode(' OR ', $onde);

        return array_map(static fn (object $b): array => (array) $b, $this->conexao()->select(
            "SELECT * FROM resgate.bloqueios {$filtro} ORDER BY registrado_em DESC",
            $valores,
        ));
    }

    /** @param array<string, mixed> $alvo */
    private function inserir(array $alvo, string $tipo, string $documento, string $motivo, int $autorId, Rastro $rastro): int
    {
        if (! in_array($tipo, ['judicial', 'administrativo'], true)) {
            throw new RegraDoResgate('Tipo de bloqueio inválido.', 'tipo');
        }
        $colunas = array_keys($alvo);

        return (int) $this->conexao()->selectOne(
            'INSERT INTO resgate.bloqueios (' . implode(', ', $colunas) . ', tipo, documento_origem, motivo, registrado_por, registrado_ip)
             VALUES (' . implode(', ', array_fill(0, count($colunas), '?')) . ', ?, ?, ?, ?, ?) RETURNING id',
            [...array_values($alvo), $tipo, $documento, $motivo, $autorId, $rastro->ip],
        )->id;
    }

    private function conexao(): Connection
    {
        return DB::connection((string) config('resgate.conexao'));
    }
}
