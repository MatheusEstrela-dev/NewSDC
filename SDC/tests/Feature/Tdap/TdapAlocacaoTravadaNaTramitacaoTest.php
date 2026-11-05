<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\Municipio;
use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Services\CronogramaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Alocacao de caminhao e exclusao x tramitacao: a tramitacao trava a linha do
 * cronograma e confere a frota; alocar/atualizar/remover/deletar tem que
 * travar a mesma linha, senao a CEDEC analisa uma frota diferente da enviada.
 *
 * Uma unica conexao nao reproduz a corrida em si; o teste garante (a) o
 * bloqueio por fase e (b) que o SELECT ... FOR UPDATE no cronograma acontece
 * dentro da transacao antes da checagem.
 */
class TdapAlocacaoTravadaNaTramitacaoTest extends TestCase
{
    use DatabaseTransactions;

    private int $cronogramaId;
    private int $prestadorId;
    private int $caminhaoId;
    private int $alocacaoId;

    protected function setUp(): void
    {
        parent::setUp();

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $ata = DB::table('tdap_atas')->insertGetId(['numero' => 'AT-' . random_int(1000, 9999), 'dt_inicio' => now()->subMonth()->toDateString(), 'dt_final' => now()->addYear()->toDateString()] + $agora);
        $this->prestadorId = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador AT', 'email' => 'at@t.local'] + $agora);
        $lote = DB::table('tdap_lotes')->insertGetId(['ata_id' => $ata, 'prestador_id' => $this->prestadorId, 'numero' => 'L-AT', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        $this->caminhaoId = $this->novoCaminhao();
        $this->cronogramaId = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-AT-' . random_int(100, 999), 'ata_id' => $ata, 'lote_id' => $lote,
            'municipio_id' => Municipio::factory()->create()->id, 'prestador_id' => $this->prestadorId,
            'dt_inicio' => now()->toDateString(), 'dt_final' => now()->addMonth()->toDateString(), 'ativo' => false,
        ] + $agora);
        $this->alocacaoId = DB::table('tdap_crono_caminhoes')->insertGetId(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $this->caminhaoId] + $agora);
    }

    private function novoCaminhao(): int
    {
        return DB::table('tdap_caminhoes')->insertGetId([
            'prestador_id' => $this->prestadorId, 'placa' => 'ATZ' . random_int(1, 9) . 'B' . random_int(10, 99),
            'capacidade_m3' => 8, 'ativo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function fase(string $fase): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['fase_tramite' => $fase]);
    }

    private function dto(int $caminhaoId): CronoCaminhaoDTO
    {
        return new CronoCaminhaoDTO($this->cronogramaId, $caminhaoId, null, 16.0, 2, 1);
    }

    /** @return array<int, string> SQL executado pelo callback, em ordem. */
    private function sqlDe(callable $acao): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $acao();
        } finally {
            $log = array_column(DB::getQueryLog(), 'query');
            DB::disableQueryLog();
        }

        return $log;
    }

    private function assertTravouCronograma(array $sql): void
    {
        $travas = array_filter($sql, fn (string $q) => str_contains($q, 'from "tdap_cronogramas"') && str_contains(strtolower($q), 'for update'));
        $this->assertNotEmpty($travas, 'esperava SELECT ... FOR UPDATE em tdap_cronogramas');
    }

    public function test_alocar_trava_o_cronograma_antes_de_gravar(): void
    {
        $novo = $this->novoCaminhao();

        $sql = $this->sqlDe(fn () => app(CronoCaminhaoService::class)->alocar($this->dto($novo)));

        $this->assertTravouCronograma($sql);
        $indiceTrava = array_key_first(array_filter($sql, fn ($q) => str_contains(strtolower($q), 'for update')));
        $indiceInsert = array_key_first(array_filter($sql, fn ($q) => str_starts_with($q, 'insert into "tdap_crono_caminhoes"')));
        $this->assertLessThan($indiceInsert, $indiceTrava);
    }

    public function test_atualizar_e_remover_travam_o_cronograma(): void
    {
        $sql = $this->sqlDe(fn () => app(CronoCaminhaoService::class)->atualizar($this->alocacaoId, $this->dto($this->caminhaoId)));
        $this->assertTravouCronograma($sql);

        $sql = $this->sqlDe(fn () => app(CronoCaminhaoService::class)->remover($this->alocacaoId));
        $this->assertTravouCronograma($sql);
        $this->assertSoftDeleted('tdap_crono_caminhoes', ['id' => $this->alocacaoId]);
    }

    #[DataProvider('fasesTravadas')]
    public function test_alocacao_recusada_em_analise_ou_aprovado(string $fase): void
    {
        $this->fase($fase);
        $svc = app(CronoCaminhaoService::class);

        foreach ([
            fn () => $svc->alocar($this->dto($this->novoCaminhao())),
            fn () => $svc->atualizar($this->alocacaoId, $this->dto($this->caminhaoId)),
            fn () => $svc->remover($this->alocacaoId),
        ] as $acao) {
            try {
                $acao();
                $this->fail('esperava DomainException');
            } catch (\DomainException $e) {
                $this->assertStringContainsString('nao aceita alteracao de caminhoes', $e->getMessage());
            }
        }

        $this->assertSame(1, CronoCaminhao::where('cronograma_id', $this->cronogramaId)->count());
    }

    #[DataProvider('fasesTravadas')]
    public function test_deletar_recusado_em_analise_ou_aprovado(string $fase): void
    {
        $this->fase($fase);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('em analise ou aprovado nao pode ser excluido');

        try {
            app(CronogramaService::class)->deletar($this->cronogramaId);
        } finally {
            $this->assertNotNull(Cronograma::find($this->cronogramaId));
        }
    }

    public function test_deletar_rascunho_em_elaboracao_continua_permitido(): void
    {
        $sql = $this->sqlDe(fn () => $this->assertTrue(app(CronogramaService::class)->deletar($this->cronogramaId)));

        $this->assertTravouCronograma($sql);
        $this->assertNull(Cronograma::find($this->cronogramaId));
    }

    /** @return array<string, array{string}> */
    public static function fasesTravadas(): array
    {
        return ['em analise' => ['em_analise'], 'aprovado' => ['aprovado']];
    }
}
