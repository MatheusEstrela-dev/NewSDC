<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Resgate\Enums\EscopoCarteira;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Detector de anomalia antes da decisao da CEDEC (plano, secao 5).
 *
 * Nao bloqueia sozinho: devolve ALERTAS que a CEDEC ve no pedido e precisa
 * declarar que conferiu para aprovar. O ponto que libera o premio veio da
 * temporada fechada, entao e ela que se examina:
 *
 *  - pico: pontos da temporada fechada muito acima da media das duas
 *          anteriores (subida abrupta perto do resgate);
 *  - concentracao: um unico usuario responde pela maior parte dos pontos do
 *          ente (a producao nao e do ente, e de uma pessoa);
 *  - ajustes: ha pedido de correcao pendente sobre pontos do ente.
 *
 * Limiares sao propostas ate o normativo; ficam aqui, nomeados.
 */
final class AnomaliasDoEnte
{
    private const PICO_MULTIPLO = 3.0;
    private const PICO_MINIMO_PONTOS = 1000;
    private const CONCENTRACAO_MAXIMA = 0.70;

    /** @return list<array{codigo: string, mensagem: string}> */
    public function avaliar(EscopoCarteira $escopo, int $enteId, DateTimeImmutable $agora): array
    {
        $db = DB::connection((string) config('resgate.conexao'));
        $coluna = $escopo->colunaDoLancamento();
        $alertas = [];

        $temporadas = [];
        [$inicio] = TipoPeriodo::Trimestre->limites($agora);
        for ($i = 0; $i < 3; $i++) {
            $referencia = $inicio->modify('-1 day');
            [$inicio, $fim] = TipoPeriodo::Trimestre->limites($referencia);
            $temporadas[] = [$inicio, $fim];
        }

        $pontos = [];
        foreach ($temporadas as [$de, $ate]) {
            $pontos[] = (int) $db->selectOne(
                "SELECT COALESCE(SUM(pontos), 0) AS p FROM ranking.lancamentos
                  WHERE {$coluna} = ? AND competencia_em >= ?::timestamptz AND competencia_em < ?::timestamptz",
                [$enteId, $de->format(DATE_ATOM), $ate->format(DATE_ATOM)],
            )->p;
        }
        [$fechada, $anterior1, $anterior2] = $pontos;
        // Divisao por 2.0: com inteiros divisiveis o PHP devolve int, e o
        // teste `=== 0.0` falharia justamente quando as anteriores zeraram.
        $media = ($anterior1 + $anterior2) / 2.0;
        if ($fechada >= self::PICO_MINIMO_PONTOS && ($media === 0.0 || $fechada > $media * self::PICO_MULTIPLO)) {
            $alertas[] = ['codigo' => 'pico', 'mensagem' => sprintf(
                'Pico de pontos: %d na temporada fechada contra média de %d nas duas anteriores.', $fechada, (int) $media,
            )];
        }

        [$de, $ate] = $temporadas[0];
        $maior = $db->selectOne(
            "SELECT credited_user_id, SUM(pontos) AS p FROM ranking.lancamentos
              WHERE {$coluna} = ? AND competencia_em >= ?::timestamptz AND competencia_em < ?::timestamptz AND credited_user_id IS NOT NULL
              GROUP BY credited_user_id ORDER BY p DESC LIMIT 1",
            [$enteId, $de->format(DATE_ATOM), $ate->format(DATE_ATOM)],
        );
        if ($maior !== null && $fechada > 0 && ((int) $maior->p / $fechada) > self::CONCENTRACAO_MAXIMA) {
            $alertas[] = ['codigo' => 'concentracao', 'mensagem' => sprintf(
                'Concentração: o usuário #%d responde por %d%% dos pontos do ente na temporada fechada.',
                (int) $maior->credited_user_id, (int) round((int) $maior->p / $fechada * 100),
            )];
        }

        $ajustes = (int) $db->selectOne(
            "SELECT count(*) AS n FROM ranking.pedidos_ajuste a
               JOIN ranking.lancamentos l ON l.id = a.lancamento_id
              WHERE l.{$coluna} = ? AND a.decisao = 'pendente'",
            [$enteId],
        )->n;
        if ($ajustes > 0) {
            $alertas[] = ['codigo' => 'ajustes', 'mensagem' => "Há {$ajustes} pedido(s) de ajuste pendente(s) sobre pontos do ente."];
        }

        return $alertas;
    }
}
