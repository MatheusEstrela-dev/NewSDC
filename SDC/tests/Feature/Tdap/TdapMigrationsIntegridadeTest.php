<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\Municipio;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integridade estrutural das tabelas do TDAP (#68, continuacao da #34):
 * chaves estrangeiras (RESTRICT, CASCADE, SET NULL) e restricoes de unicidade
 * criadas pelas migrations tdap_*.
 *
 * DatabaseTransactions e nao RefreshDatabase: os testes rodam no banco de
 * desenvolvimento e RefreshDatabase apagaria todas as tabelas. Cada violacao
 * esperada roda num savepoint (DB::transaction aninhada), porque no Postgres
 * um erro dentro da transacao do teste a invalidaria inteira.
 */
class TdapMigrationsIntegridadeTest extends TestCase
{
    use DatabaseTransactions;

    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->municipioId = Municipio::factory()->create()->id;
    }

    private function agora(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }

    private function ata(): int
    {
        return DB::table('tdap_atas')->insertGetId([
            'numero' => 'ATA-IT-' . uniqid(), 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31',
        ] + $this->agora());
    }

    private function prestador(string $cnpj = null): int
    {
        return DB::table('tdap_prestadores')->insertGetId([
            'cnpj' => $cnpj ?? (string) random_int(10000000000000, 99999999999999),
            'nome' => 'Prestador IT', 'email' => 'it@teste.local',
        ] + $this->agora());
    }

    private function lote(int $ataId, int $prestadorId): int
    {
        return DB::table('tdap_lotes')->insertGetId([
            'ata_id' => $ataId, 'prestador_id' => $prestadorId, 'numero' => 'L-' . uniqid(),
            'qtd_agua_m3' => 100, 'valor_m3' => 12.5,
        ] + $this->agora());
    }

    /** Executa num savepoint e devolve se o banco recusou. */
    private function bancoRecusa(callable $operacao): bool
    {
        try {
            DB::transaction($operacao);

            return false;
        } catch (QueryException) {
            return true;
        }
    }

    // ── Chaves estrangeiras ─────────────────────────────────────────────────

    public function test_lote_exige_ata_e_prestador_existentes(): void
    {
        $prestador = $this->prestador();

        $this->assertTrue($this->bancoRecusa(fn () => $this->lote(999999999, $prestador)), 'ata inexistente');
        $this->assertTrue($this->bancoRecusa(fn () => $this->lote($this->ata(), 999999999)), 'prestador inexistente');
    }

    public function test_ata_e_prestador_com_lote_nao_sao_apagados_restrict(): void
    {
        $ata = $this->ata();
        $prestador = $this->prestador();
        $this->lote($ata, $prestador);

        $this->assertTrue($this->bancoRecusa(fn () => DB::table('tdap_atas')->where('id', $ata)->delete()));
        $this->assertTrue($this->bancoRecusa(fn () => DB::table('tdap_prestadores')->where('id', $prestador)->delete()));
        $this->assertTrue(DB::table('tdap_atas')->where('id', $ata)->exists());
    }

    public function test_municipios_do_lote_saem_junto_com_o_lote_cascade(): void
    {
        $lote = $this->lote($this->ata(), $this->prestador());
        DB::table('tdap_lote_municipios')->insert(['lote_id' => $lote, 'municipio_id' => $this->municipioId] + $this->agora());

        DB::table('tdap_lotes')->where('id', $lote)->delete();

        $this->assertFalse(DB::table('tdap_lote_municipios')->where('lote_id', $lote)->exists());
    }

    public function test_cronograma_exige_vinculos_validos_e_perde_o_ponto_apagado_set_null(): void
    {
        $ata = $this->ata();
        $prestador = $this->prestador();
        $lote = $this->lote($ata, $prestador);
        $ponto = DB::table('pip_pmda_ponto')->insertGetId(['municipio_id' => $this->municipioId, 'nome' => 'Poco IT'] + $this->agora());

        $base = [
            'numero' => 'CR-' . uniqid(), 'ata_id' => $ata, 'lote_id' => $lote, 'prestador_id' => $prestador,
            'municipio_id' => $this->municipioId, 'dt_inicio' => '2026-02-01', 'dt_final' => '2026-02-28',
        ] + $this->agora();

        $this->assertTrue($this->bancoRecusa(fn () => DB::table('tdap_cronogramas')->insert(['municipio_id' => 999999999] + $base)));

        $cronograma = DB::table('tdap_cronogramas')->insertGetId(['ponto_captacao_id' => $ponto] + $base);
        DB::table('pip_pmda_ponto')->where('id', $ponto)->delete();

        $this->assertNull(DB::table('tdap_cronogramas')->where('id', $cronograma)->value('ponto_captacao_id'));
        $this->assertTrue($this->bancoRecusa(fn () => DB::table('tdap_lotes')->where('id', $lote)->delete()), 'lote em uso por cronograma');
    }

    // ── Unicidade ───────────────────────────────────────────────────────────

    public function test_cnpj_unico_entre_prestadores_ativos_e_reutilizavel_apos_exclusao(): void
    {
        $cnpj = (string) random_int(10000000000000, 99999999999999);
        $primeiro = $this->prestador($cnpj);

        $this->assertTrue($this->bancoRecusa(fn () => $this->prestador($cnpj)));

        DB::table('tdap_prestadores')->where('id', $primeiro)->update(['deleted_at' => now()]);
        $this->assertFalse($this->bancoRecusa(fn () => $this->prestador($cnpj)), 'indice parcial: excluido libera o CNPJ');
    }

    public function test_placa_unica_por_prestador_e_municipio_unico_por_lote(): void
    {
        $prestador = $this->prestador();
        $caminhao = fn () => DB::table('tdap_caminhoes')->insert(['prestador_id' => $prestador, 'placa' => 'ITT1A23', 'capacidade_m3' => 16] + $this->agora());

        $caminhao();
        $this->assertTrue($this->bancoRecusa($caminhao));

        $lote = $this->lote($this->ata(), $prestador);
        $vinculo = fn () => DB::table('tdap_lote_municipios')->insert(['lote_id' => $lote, 'municipio_id' => $this->municipioId] + $this->agora());

        $vinculo();
        $this->assertTrue($this->bancoRecusa($vinculo));
    }

    public function test_numero_da_ata_e_unico(): void
    {
        $numero = 'AU-' . uniqid(); // coluna varchar(20)
        $ata = fn () => DB::table('tdap_atas')->insert(['numero' => $numero, 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'] + $this->agora());

        $ata();
        $this->assertTrue($this->bancoRecusa($ata));
    }
}
