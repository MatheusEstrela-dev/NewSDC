<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Ranking\DTOs\ScoreDecisionData;
use App\Modules\Ranking\Enums\DecisaoPontuacao;
use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Models\Regra;
use App\Modules\Ranking\Services\PeriodoService;
use App\Modules\Ranking\Services\RecordScoreTransaction;
use App\Modules\Ranking\Services\ReverseScoreEntry;
use App\Modules\Ranking\Support\CatalogoRegras;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/** Dados sinteticos exclusivos da homologacao local; nunca integra o DatabaseSeeder. */
final class RankingDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! in_array(parse_url((string) config('app.url'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true)
            || DB::connection('ranking')->getDatabaseName() !== 'sdc_ranking') {
            throw new RuntimeException('Demonstracao permitida apenas no ranking da homologacao local.');
        }

        $usuario = User::query()->findOrFail(1);
        $orgao = $usuario->orgaoPrincipal;
        $data = new DateTimeImmutable('first day of this month 12:00', new DateTimeZone('America/Sao_Paulo'));
        $catalogo = CatalogoRegras::todos();
        $livro = app(RecordScoreTransaction::class);
        $periodos = app(PeriodoService::class);

        DB::connection('ranking')->transaction(function () use ($usuario, $orgao, $data, $catalogo, $livro, $periodos): void {
            // Identidades sinteticas somente na base local do ranking, sem criar contas SDC.
            $participantes = [900000001 => 1600, 900000002 => 900, 900000003 => 900, (int) $usuario->id => 450, 900000004 => 125, 900000005 => 0];
            foreach ($participantes as $id => $alvo) {
                $orgaoId = $id === (int) $usuario->id ? $orgao?->id : $id;
                $municipioId = $id === (int) $usuario->id ? $orgao?->municipio_id : $id;
                foreach (TipoPeriodo::cases() as $tipo) {
                    $periodo = $periodos->resolverPeriodo($tipo, $data);
                    foreach ([EscopoPlacar::Usuario->value => $id, EscopoPlacar::Orgao->value => $orgaoId, EscopoPlacar::Municipio->value => $municipioId] as $escopo => $entidade) {
                        if ($entidade !== null) {
                            $periodos->registrarParticipante($periodo, EscopoPlacar::from($escopo), (int) $entidade, 'DEMONSTRACAO', 'demo');
                        }
                    }
                }
                $restante = $alvo;
                for ($indice = 0; $restante > 0; $indice++) {
                    $regra = $catalogo[$indice % count($catalogo)];
                    $base = min((int) $regra['pontos_base'], $restante);
                    $this->registrar($livro, $data, $regra, $id, $orgaoId, $municipioId, 'credito-'.$indice, new ScoreDecisionData(DecisaoPontuacao::Confirmada, $base, 0, 'demonstracao_homologacao'));
                    $restante -= $base;
                }
            }

            $regra = $catalogo[1];
            foreach ([
                'bonus' => new ScoreDecisionData(DecisaoPontuacao::Confirmada, 20, 4, 'demonstracao_bonus_no_prazo'),
                'pendente' => new ScoreDecisionData(DecisaoPontuacao::Pendente, 20, 0, 'demonstracao_aguardando_validacao'),
                'zero' => new ScoreDecisionData(DecisaoPontuacao::Zero, 0, 0, 'demonstracao_rascunho_sem_premio'),
                'apuracao' => new ScoreDecisionData(DecisaoPontuacao::EmApuracao, 0, 0, 'demonstracao_autoria_nao_comprovada'),
                'estorno' => new ScoreDecisionData(DecisaoPontuacao::Confirmada, 20, 0, 'demonstracao_credito_cancelado'),
            ] as $cenario => $decisao) {
                $transacao = $this->registrar($livro, $data, $regra, (int) $usuario->id, $orgao?->id, $orgao?->municipio_id, $cenario, $decisao);
                if ($cenario === 'estorno' && $transacao->decisao !== DecisaoPontuacao::Estornada) {
                    app(ReverseScoreEntry::class)->estornar((int) $transacao->lancamentos()->whereNull('estorno_de_id')->firstOrFail()->id);
                }
            }
        });
        $this->command?->info('Demonstracao criada: 6 participantes, empate em 900, todas as faixas e extrato do usuario 1. Reexecucao nao duplica pontos.');
    }

    private function registrar(RecordScoreTransaction $livro, DateTimeImmutable $data, array $catalogo, int $usuario, ?int $orgao, ?int $municipio, string $cenario, ScoreDecisionData $decisao): \App\Modules\Ranking\Models\Transacao
    {
        $familia = 'demo_'.$catalogo['familia'];
        $regra = Regra::query()->firstOrCreate(['rule_key' => 'demo.'.$catalogo['rule_key'], 'versao' => 1], [
            'modulo' => $catalogo['modulo'], 'familia' => $familia, 'pontos_base' => $catalogo['pontos_base'],
            'aceita_bonus' => true, 'bonus_percentual' => 20, 'habilitada' => false,
            'motivo_desabilitada' => 'demonstracao_homologacao', 'vigente_de' => '2026-01-01 00:00:00+00:00',
        ]);
        $chave = 'demo:v1:'.$data->format('Y-m').':'.$usuario.':'.$cenario;

        return $livro->registrar(
            eventId: Uuid::uuid5(Uuid::NAMESPACE_URL, $chave)->toString(), eventName: 'ranking.demo',
            chaveCanonica: $chave, familia: $familia, modulo: strtolower($catalogo['modulo']),
            decisao: $decisao, ocorridoEm: $data, competenciaEm: $data,
            regraId: (int) $regra->id, regraVersao: 1, actorUserId: $usuario, creditedUserId: $usuario,
            orgaoId: $orgao, municipioId: $municipio, contexto: ['demonstracao' => true, 'cenario' => $cenario],
        );
    }
}
