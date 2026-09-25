<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Ranking\DTOs\ScoreDecisionData;
use App\Modules\Ranking\Enums\DecisaoPontuacao;
use App\Modules\Ranking\Services\RecordScoreTransaction;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Placar de demonstracao com os PARTICIPANTES REAIS do mes corrente e pontos
 * aleatorios, para ver a classificacao como ficaria na pratica.
 *
 * Grava pelo caminho oficial (RecordScoreTransaction): ledger, transacao e
 * projecao nas tres dimensoes, com orgao e municipio do vinculo vigente. Usa as
 * regras vigentes do catalogo, sem criar regras 'demo.*' no modal.
 *
 * Toda chave comeca por 'demo:aleatorio:' e o contexto leva demonstracao=true:
 * e o criterio de purga. Semente fixa (RANKING_DEMO_SEMENTE) reproduz o mesmo
 * placar; reexecutar nao duplica, a chave canonica e idempotente.
 *
 * Nunca integra o DatabaseSeeder. Exclusivo da homologacao local.
 */
final class RankingDemoAleatorioSeeder extends Seeder
{
    /** Distribuicao por faixa: [faixa, fatia acumulada, pontos minimos, pontos maximos]. */
    private const DISTRIBUICAO = [
        ['diamante', 0.015, 7000, 8500],
        ['ouro', 0.075, 700, 6999],
        ['prata', 0.25, 300, 699],
        ['bronze', 0.70, 15, 299],
        ['sem_pontos', 1.0, 0, 0],
    ];

    /** Chance de a entrega sair no prazo e levar bonus. */
    private const CHANCE_NO_PRAZO = 0.45;

    public function run(): void
    {
        if (! in_array(parse_url((string) config('app.url'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true)
            || DB::connection('ranking')->getDatabaseName() !== 'sdc_ranking') {
            throw new RuntimeException('Demonstracao permitida apenas no ranking da homologacao local.');
        }

        mt_srand((int) env('RANKING_DEMO_SEMENTE', 2026));
        $fuso = new DateTimeZone('America/Sao_Paulo');
        $agora = new DateTimeImmutable('now', $fuso);
        $inicioMes = $agora->modify('first day of this month')->setTime(8, 0);
        $db = DB::connection('ranking');

        $regras = $db->select(
            "SELECT id, versao, rule_key, modulo, familia, pontos_base, bonus_percentual
               FROM ranking.regras
              WHERE vigente_ate IS NULL AND pontos_base > 0 AND rule_key NOT LIKE 'demo.%'
              ORDER BY rule_key"
        );
        if ($regras === []) {
            throw new RuntimeException('Catalogo sem regras vigentes com pontos.');
        }

        $participantes = array_map('intval', array_column($db->select(
            "SELECT DISTINCT pt.entidade_id
               FROM ranking.participantes pt
               JOIN ranking.periodos p ON p.id = pt.periodo_id
              WHERE pt.escopo = 'usuario' AND p.chave = ?
              ORDER BY pt.entidade_id",
            ['mes:' . $agora->format('Y-m')],
        ), 'entidade_id'));

        $vinculos = [];
        foreach ($db->select('SELECT DISTINCT ON (user_id) user_id, orgao_id, municipio_id
                                FROM ranking.vinculos
                               WHERE valido_ate IS NULL
                               ORDER BY user_id, valido_de DESC') as $vinculo) {
            $vinculos[(int) $vinculo->user_id] = $vinculo;
        }

        $livro = app(RecordScoreTransaction::class);
        $segundosNoMes = max(3600, $agora->getTimestamp() - $inicioMes->getTimestamp());
        $porFaixa = array_fill_keys(array_column(self::DISTRIBUICAO, 0), 0);
        $lancamentos = 0;

        foreach ($participantes as $usuarioId) {
            // O admin (id 1) fica no Diamante para a celebracao ser visivel.
            [$faixa, $alvo] = $usuarioId === 1 ? ['diamante', mt_rand(7100, 7800)] : $this->sortearAlvo();
            $porFaixa[$faixa]++;
            $vinculo = $vinculos[$usuarioId] ?? null;

            $db->transaction(function () use ($livro, $regras, $usuarioId, $alvo, $vinculo, $inicioMes, $segundosNoMes, $fuso, &$lancamentos): void {
                for ($n = 0, $restante = $alvo; $restante > 0; $n++) {
                    $regra = $regras[mt_rand(0, count($regras) - 1)];
                    $base = min((int) $regra->pontos_base, $restante);
                    // Bonus limitado ao que falta: o alvo sorteado nao pula de faixa.
                    $bonus = mt_rand() / mt_getrandmax() < self::CHANCE_NO_PRAZO
                        ? min(intdiv($base * (int) $regra->bonus_percentual, 100), $restante - $base)
                        : 0;
                    $quando = $inicioMes->setTimestamp($inicioMes->getTimestamp() + mt_rand(0, $segundosNoMes))->setTimezone($fuso);
                    $chave = 'demo:aleatorio:v1:' . $inicioMes->format('Y-m') . ":{$usuarioId}:{$n}";

                    $livro->registrar(
                        eventId: Uuid::uuid5(Uuid::NAMESPACE_URL, $chave)->toString(),
                        eventName: 'ranking.demo',
                        chaveCanonica: $chave,
                        familia: (string) $regra->familia,
                        modulo: strtolower((string) $regra->modulo),
                        decisao: new ScoreDecisionData(DecisaoPontuacao::Confirmada, $base, $bonus, $bonus > 0 ? 'demonstracao_no_prazo' : 'demonstracao_entrega'),
                        ocorridoEm: $quando,
                        competenciaEm: $quando,
                        regraId: (int) $regra->id,
                        regraVersao: (int) $regra->versao,
                        actorUserId: $usuarioId,
                        creditedUserId: $usuarioId,
                        orgaoId: $vinculo?->orgao_id !== null ? (int) $vinculo->orgao_id : null,
                        municipioId: $vinculo?->municipio_id !== null ? (int) $vinculo->municipio_id : null,
                        contexto: ['demonstracao' => true, 'cenario' => 'aleatorio'],
                    );
                    $restante -= $base + $bonus;
                    $lancamentos++;
                }
            });
        }

        $resumo = implode(', ', array_map(fn ($f, $q) => "{$f}={$q}", array_keys($porFaixa), $porFaixa));
        $this->command?->info("Demonstracao aleatoria: " . count($participantes) . " participantes, {$lancamentos} lancamentos ({$resumo}).");
    }

    /** @return array{0: string, 1: int} */
    private function sortearAlvo(): array
    {
        $sorteio = mt_rand() / mt_getrandmax();
        foreach (self::DISTRIBUICAO as [$faixa, $fatia, $minimo, $maximo]) {
            if ($sorteio <= $fatia) {
                return [$faixa, $maximo > 0 ? mt_rand($minimo, $maximo) : 0];
            }
        }

        return ['sem_pontos', 0];
    }
}
