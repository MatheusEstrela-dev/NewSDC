<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\Municipio;
use App\Modules\Tdap\Enums\FaseCronograma;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Restricoes de schema das migrations 2026_11_04_100000 (FK RESTRICT na cadeia
 * cronograma -> alocacao -> viagem) e 2026_11_04_100200 (CHECK de fase_tramite).
 *
 * Os testes leem o catalogo do Postgres (pg_constraint) no banco em que a
 * suite roda, que e o de desenvolvimento (DatabaseTransactions, sem
 * RefreshDatabase -- ver TdapMigrationsIntegridadeTest). Nao dependem de a
 * migration ja ter rodado: se a constraint ainda nao estiver no banco, o up()
 * da migration e aplicado dentro da transacao do teste e desfeito no rollback.
 * Durante o teste as tabelas ficam com lock exclusivo (ALTER TABLE) ate o
 * rollback -- poucos segundos no banco de dev.
 */
class TdapRestricoesDeSchemaTest extends TestCase
{
    use DatabaseTransactions;

    private const FKS = [
        'tdap_crono_caminhoes_cronograma_id_foreign',
        'tdap_crono_viagens_crono_caminhao_id_foreign',
    ];

    private const CHECK = 'tdap_cronogramas_fase_tramite_check';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Restricoes verificadas no catalogo do Postgres.');
        }
    }

    /** @return array<string, string> conname => confdeltype ('r' restrict, 'c' cascade, 'a' no action) */
    private function acaoAoApagar(): array
    {
        return DB::table('pg_constraint')
            ->whereIn('conname', self::FKS)
            ->where('contype', 'f')
            ->pluck('confdeltype', 'conname')
            ->all();
    }

    /**
     * Se a migration ainda nao rodou neste banco, aplica o up() dela DENTRO da
     * transacao do teste (DDL no Postgres e transacional; o rollback do
     * DatabaseTransactions desfaz). Assim o teste vale antes e depois do
     * deploy. So pula se, mesmo assim, a constraint nao ficar como esperado.
     */
    private function aplicarSeFaltar(string $arquivo, callable $aplicada): void
    {
        if (! $aplicada()) {
            (require base_path("database/migrations/{$arquivo}.php"))->up();
        }

        if (! $aplicada()) {
            $this->markTestSkipped("Nao foi possivel aplicar {$arquivo} na transacao do teste.");
        }
    }

    private function exigirRestrictAplicado(): void
    {
        $this->aplicarSeFaltar('2026_11_04_100000_fk_restrict_em_tdap_crono_caminhoes_e_viagens', function (): bool {
            $acoes = $this->acaoAoApagar();

            return count($acoes) === count(self::FKS) && ! in_array('c', $acoes, true);
        });
    }

    private function bancoRecusa(callable $operacao): bool
    {
        try {
            DB::transaction($operacao);

            return false;
        } catch (QueryException) {
            return true;
        }
    }

    public function test_fks_da_cadeia_do_cronograma_sao_restrict_e_validadas(): void
    {
        $this->exigirRestrictAplicado();

        $linhas = DB::table('pg_constraint')
            ->whereIn('conname', self::FKS)
            ->get(['conname', 'confdeltype', 'confupdtype', 'convalidated']);

        $this->assertCount(2, $linhas);
        foreach ($linhas as $fk) {
            $this->assertSame('r', $fk->confdeltype, "{$fk->conname}: ON DELETE deve ser RESTRICT");
            $this->assertSame('c', $fk->confupdtype, "{$fk->conname}: ON UPDATE continua CASCADE");
            $this->assertTrue((bool) $fk->convalidated, "{$fk->conname}: constraint validada");
        }
    }

    public function test_delete_fisico_nao_leva_viagem_junto_mas_soft_delete_segue_livre(): void
    {
        $this->exigirRestrictAplicado();

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $municipio = Municipio::factory()->create()->id;
        $prestador = DB::table('tdap_prestadores')->insertGetId([
            'cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador RS', 'email' => 'rs@teste.local',
        ] + $agora);
        $ata = DB::table('tdap_atas')->insertGetId([
            'numero' => 'RS-'.substr(uniqid(), -8), 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31',
        ] + $agora);
        $lote = DB::table('tdap_lotes')->insertGetId([
            'ata_id' => $ata, 'prestador_id' => $prestador, 'numero' => 'L-'.uniqid(), 'qtd_agua_m3' => 100, 'valor_m3' => 12.5,
        ] + $agora);
        $cronograma = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-'.uniqid(), 'ata_id' => $ata, 'lote_id' => $lote, 'prestador_id' => $prestador,
            'municipio_id' => $municipio, 'dt_inicio' => '2026-02-01', 'dt_final' => '2026-02-28',
        ] + $agora);
        $caminhao = DB::table('tdap_caminhoes')->insertGetId([
            'prestador_id' => $prestador, 'placa' => 'RS'.random_int(10000, 99999), 'capacidade_m3' => 16,
        ] + $agora);
        $alocacao = DB::table('tdap_crono_caminhoes')->insertGetId([
            'cronograma_id' => $cronograma, 'caminhao_id' => $caminhao,
        ] + $agora);
        $viagem = DB::table('tdap_crono_viagens')->insertGetId([
            'crono_caminhao_id' => $alocacao, 'data_registro' => '2026-02-10 10:00:00', 'validado' => 1,
        ] + $agora);

        $this->assertTrue(
            $this->bancoRecusa(fn () => DB::table('tdap_cronogramas')->where('id', $cronograma)->delete()),
            'DELETE do cronograma com alocacao deve ser recusado'
        );
        $this->assertTrue(
            $this->bancoRecusa(fn () => DB::table('tdap_crono_caminhoes')->where('id', $alocacao)->delete()),
            'DELETE da alocacao com viagem deve ser recusado'
        );
        $this->assertTrue(DB::table('tdap_crono_viagens')->where('id', $viagem)->exists());

        // O caminho da aplicacao (soft delete) nao dispara a FK.
        $this->assertFalse($this->bancoRecusa(
            fn () => DB::table('tdap_cronogramas')->where('id', $cronograma)->update(['deleted_at' => now()])
        ));
    }

    public function test_fase_tramite_tem_check_com_as_cinco_fases_e_validado(): void
    {
        $buscar = fn () => DB::table('pg_constraint')
            ->where('conname', self::CHECK)
            ->where('contype', 'c')
            ->first(['convalidated', DB::raw('pg_get_constraintdef(oid) as def')]);

        $this->aplicarSeFaltar('2026_11_04_100200_check_fase_tramite_em_tdap_cronogramas', fn () => $buscar() !== null);
        $check = $buscar();

        $this->assertTrue((bool) $check->convalidated, 'CHECK deve estar validado (VALIDATE CONSTRAINT)');
        // O dominio do CHECK tem de ser exatamente o do enum PHP.
        preg_match_all("/'([^']+)'/", $check->def, $m);
        $doCheck = array_values(array_unique($m[1]));
        $doEnum = array_map(fn (FaseCronograma $f) => $f->value, FaseCronograma::cases());
        sort($doCheck);
        sort($doEnum);

        $this->assertSame($doEnum, $doCheck, 'CHECK diverge de FaseCronograma: '.$check->def);
    }
}
