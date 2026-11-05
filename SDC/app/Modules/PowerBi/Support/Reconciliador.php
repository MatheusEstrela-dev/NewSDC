<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Modules\PowerBi\Contracts\Materializador;
use App\Support\Database\ConexaoPowerBi;

/**
 * Anti-join de chaves fonte x destino: o que sumiu da fonte vira removido=true
 * (o BI recebe a exclusao pelo keyset, pois materializado_em avanca). O que
 * existe na fonte e falta no destino conta como divergente (o proximo ciclo
 * completo ou um --desde antigo corrige). Registra em controle.reconciliacoes.
 */
final class Reconciliador
{
    public function __construct(private readonly RegistroDeExecucoes $registro)
    {
    }

    /**
     * @return array<string, array{origem: int, destino: int, removidas: int, divergentes: int}>
     */
    public function reconciliar(Materializador $m, ?string $so = null): array
    {
        JanelaDaFonte::obter(); // aplica a trava de standby antes de ler a fonte

        $saida = [];
        foreach ($m->tabelas() as $nome => $d) {
            // Filhas seguem o pai (apaga e reinsere); nao tem existencia propria.
            if ($d->ehFilha() || ($so !== null && $so !== $nome)) {
                continue;
            }
            $saida[$nome] = $this->tabela($m, $d);
        }

        return $saida;
    }

    /** @return array{origem: int, destino: int, removidas: int, divergentes: int} */
    private function tabela(Materializador $m, DefinicaoTabela $d): array
    {
        $id = $this->registro->iniciar($d->modulo, 'reconciliacao', $d->nome);

        try {
            $r = ConexaoPowerBi::escrita()->transaction(fn (): array => $this->anti($m, $d));
        } catch (\Throwable $e) {
            $this->registro->falhar($id, $e);
            throw $e;
        }

        ConexaoPowerBi::tabela('controle.reconciliacoes')->insert([
            'modulo' => $d->modulo,
            'tabela' => $d->nome,
            'linhas_origem' => $r['origem'],
            'linhas_destino' => $r['destino'],
            'removidas' => $r['removidas'],
            'divergentes' => $r['divergentes'],
            'detalhe' => json_encode(['fonte_vazia_ignorada' => $r['ignorada']], JSON_THROW_ON_ERROR),
        ]);
        $this->registro->concluir($id, (new ResultadoExecucao())->registrar(
            $d->nome,
            new ResultadoGravacao($r['origem'], 0, $r['removidas'])
        ));

        return array_intersect_key($r, array_flip(['origem', 'destino', 'removidas', 'divergentes']));
    }

    /** @return array{origem: int, destino: int, removidas: int, divergentes: int, ignorada: bool} */
    private function anti(Materializador $m, DefinicaoTabela $d): array
    {
        $db = ConexaoPowerBi::escrita();
        $pk = implode(', ', $d->pk);

        $db->statement("DROP TABLE IF EXISTS pg_temp.rec_{$d->nome}");
        $db->statement("CREATE TEMP TABLE rec_{$d->nome} ON COMMIT DROP AS SELECT {$pk} FROM {$d->alvo()} WITH NO DATA");
        $db->statement("ALTER TABLE pg_temp.rec_{$d->nome} ADD PRIMARY KEY ({$pk})");

        $porLinha = '('.implode(', ', array_fill(0, count($d->pk), '?')).')';
        $porStatement = max(1, intdiv(65535, count($d->pk)));
        $lote = [];
        $enviar = function () use (&$lote, $d, $db, $pk, $porLinha): void {
            if ($lote === []) {
                return;
            }
            $db->insert(
                "INSERT INTO pg_temp.rec_{$d->nome} ({$pk}) VALUES ".implode(', ', array_fill(0, count($lote), $porLinha)).' ON CONFLICT DO NOTHING',
                array_merge(...$lote)
            );
            $lote = [];
        };
        foreach ($m->chavesDaFonte($d->nome) as $chave) {
            $lote[] = array_map(fn (string $c): mixed => $chave[$c], $d->pk);
            if (count($lote) >= min((int) config('powerbi.lote', 1000), $porStatement)) {
                $enviar();
            }
        }
        $enviar();

        $origem = (int) $db->selectOne("SELECT count(*) AS n FROM pg_temp.rec_{$d->nome}")->n;
        $destino = (int) $db->selectOne("SELECT count(*) AS n FROM {$d->alvo()} WHERE NOT removido")->n;
        $junta = implode(' AND ', array_map(fn (string $c): string => "r.{$c} = a.{$c}", $d->pk));

        $divergentes = (int) $db->selectOne(
            "SELECT count(*) AS n FROM pg_temp.rec_{$d->nome} r
             WHERE NOT EXISTS (SELECT 1 FROM {$d->alvo()} a WHERE {$junta} AND NOT a.removido)"
        )->n;

        // Fonte vazia com destino cheio e quase sempre erro de leitura: nao apaga em massa.
        $ignorada = $origem === 0 && $destino > 0;
        $removidas = $ignorada ? 0 : $db->affectingStatement(
            "UPDATE {$d->alvo()} a SET removido = true, materializado_em = now()
             WHERE NOT a.removido AND NOT EXISTS (SELECT 1 FROM pg_temp.rec_{$d->nome} r WHERE {$junta})"
        );

        return compact('origem', 'destino', 'removidas', 'divergentes', 'ignorada');
    }
}
