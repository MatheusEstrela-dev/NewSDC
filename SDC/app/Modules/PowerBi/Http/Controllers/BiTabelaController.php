<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PowerBi\Http\ParametrosConsultaBi;
use App\Modules\PowerBi\Support\CatalogoBi;
use App\Modules\PowerBi\Support\DefinicaoTabela;
use App\Support\Database\ConexaoPowerBi;
use App\Support\Database\LeituraConsistentePostgres;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * GET /api/v1/bi/{modulo}/{tabela}: leitura paginada por keyset
 * (materializado_em, pk) do sdc_powerbi. So tabelas e colunas do catalogo.
 *
 * Linhas removidas na fonte voltam com removido=true (o BI aplica a exclusao).
 * Cerca de leitura: so devolve linhas anteriores ao inicio da execucao em
 * curso do modulo, pois materializado_em e o now() da transacao de gravacao e
 * um commit tardio com timestamp antigo escaparia do keyset.
 */
final class BiTabelaController extends Controller
{
    public function __invoke(Request $request, CatalogoBi $catalogo, string $modulo, string $tabela): JsonResponse
    {
        if (! ConexaoPowerBi::ativa()) {
            return response()->json(['message' => 'Saida de BI desligada neste ambiente.'], 503);
        }

        $d = $catalogo->definicao($modulo, $tabela);
        if ($d === null) {
            return response()->json(['message' => 'Tabela de BI inexistente.'], 404);
        }

        try {
            $parametros = ParametrosConsultaBi::de($request->query(), $d);

            return $this->pagina($d, $parametros);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Parametros de consulta invalidos.', 'errors' => $e->errors()], 422);
        }
    }

    private function pagina(DefinicaoTabela $d, ParametrosConsultaBi $parametros): JsonResponse
    {
        $db = ConexaoPowerBi::leitura();
        // Cerca, linhas e referencia pertencem ao mesmo snapshot desta pagina.
        // A proxima requisicao abre outro snapshot e retoma pelo keyset.
        return LeituraConsistentePostgres::executar($db, function () use ($db, $d, $parametros): JsonResponse {
            $consulta = $this->consulta($db, $d, $parametros, $this->cerca($db, $d->modulo));

            try {
                $linhas = $consulta->get();
            } catch (QueryException $e) {
                // PKs e filtros variam por tabela. O PostgreSQL valida seus
                // tipos; somente erros de conversao da consulta viram 422.
                if (in_array($e->errorInfo[0] ?? null, ['22P02', '22003', '22007', '22008'], true)) {
                    throw ValidationException::withMessages(['consulta' => 'Cursor ou filtro incompativel com os tipos da tabela.']);
                }
                throw $e;
            }
            $proximo = null;
            if ($linhas->count() > $parametros->limite) {
                $linhas = $linhas->take($parametros->limite);
                $proximo = $this->codificar($linhas->last(), $d);
            }

            $dadosAte = $db->table('controle.cursores')->where('modulo', $d->modulo)->where('tabela', $d->nome)->value('fonte_replay_em');
            $dadosAte = $dadosAte !== null ? Carbon::parse($dadosAte)->toIso8601String() : null;

            $dados = $linhas->map(fn (object $l): array => $this->saida($l, $d))->values()->all();

            return response()->json(
                ['dados' => $dados, 'proximo_cursor' => $proximo, 'dados_ate' => $dadosAte],
                200,
                $dadosAte !== null ? ['X-Dados-Ate' => $dadosAte] : []
            );
        });
    }

    private function consulta(Connection $db, DefinicaoTabela $d, ParametrosConsultaBi $parametros, ?string $cerca): Builder
    {
        $colunas = array_values(array_unique([...$d->expostas, ...$d->pk]));
        $q = $db->table($d->alvo())
            ->select($colunas)
            ->addSelect('materializado_em', 'removido')
            ->selectRaw('materializado_em::text AS _m');

        foreach ($parametros->filtros as $coluna => $valor) {
            $q->where($coluna, $valor);
        }
        if ($parametros->desde !== null) {
            $q->where('materializado_em', '>', $parametros->desde);
        }
        if ($cerca !== null) {
            $q->where('materializado_em', '<', $cerca);
        }
        if ($parametros->apos !== null) {
            $q->whereRaw(
                '(materializado_em, '.implode(', ', $d->pk).') > (?, '.implode(', ', array_fill(0, count($d->pk), '?')).')',
                $parametros->apos
            );
        }

        $q->orderBy('materializado_em');
        foreach ($d->pk as $c) {
            $q->orderBy($c);
        }

        return $q->limit($parametros->limite + 1);
    }

    /** Inicio da execucao mais antiga em curso do modulo (ignora travadas ha mais de 2h). */
    private function cerca(Connection $db, string $modulo): ?string
    {
        $f = $db->selectOne(
            "SELECT min(iniciada_em)::text AS f FROM controle.execucoes
             WHERE modulo = ? AND status = 'executando' AND iniciada_em > now() - interval '2 hours'",
            [$modulo]
        );

        return $f?->f;
    }

    /** @return array<string, mixed> */
    private function saida(object $linha, DefinicaoTabela $d): array
    {
        $dados = (array) $linha;
        $saida = [];
        foreach ([...$d->expostas, 'materializado_em', 'removido'] as $c) {
            $saida[$c] = $dados[$c];
        }

        return $saida;
    }

    private function codificar(object $ultima, DefinicaoTabela $d): string
    {
        $dados = (array) $ultima;
        $partes = [$dados['_m'], ...array_map(fn (string $c): mixed => $dados[$c], $d->pk)];

        return rtrim(strtr(base64_encode(json_encode($partes, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
