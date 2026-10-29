<?php

declare(strict_types=1);

namespace Tests\Feature\Pmda;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Services\ComunidadeService;
use App\Modules\Pmda\Services\ComunidadeSolicitacaoService;
use App\Modules\Pmda\Support\IssDaPrefeitura;
use App\Modules\Pmda\Support\NomeDeLugar;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Services\CronogramaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * #56 (PMDA): fallback e trava da aba ISS pela Prefeitura, filtro por ano,
 * edicao retirada de plano nao editavel, ponto de captacao em dropdown com
 * fallback, 1 comunidade por PMDA, homonimos ("Olhos d'Agua") e 1 TDAP por
 * municipio.
 */
class PmdaRegrasIssue56Test extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->user = $this->user->fresh();
        $this->actingAs($this->user);

        $this->municipioId = Municipio::factory()->create()->id;
    }

    private function plano(string $status = 'RASCUNHO', string $data = '2026-05-10'): PmdaPlano
    {
        $id = DB::table('pmda_planos')->insertGetId([
            'municipio_id' => $this->municipioId, 'status' => $status, 'data' => $data,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return PmdaPlano::findOrFail($id);
    }

    private function prefeitura(): void
    {
        DB::table('compdec_prefeituras')->insert([
            'municipio_id' => $this->municipioId, 'prefeito_nome' => 'Prefeita da Prefeitura',
            'tel_prefeitura' => '3133334444', 'endereco' => 'Praca Central, 1', 'bairro' => 'Centro', 'cep' => '30000000',
            'inss_tem_cobranca' => true, 'inss_aliquota' => 2.5, 'inss_lei_cobranca' => 'Lei 10/2020',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ponto(string $nome): int
    {
        return DB::table('pip_pmda_ponto')->insertGetId(['municipio_id' => $this->municipioId, 'nome' => $nome, 'ativo' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function comunidade(string $nome): int
    {
        return DB::table('comunidades')->insertGetId(['municipio_id' => $this->municipioId, 'nome' => $nome, 'ativo' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    // ── Aba ISS ────────────────────────────────────────────────────────────

    public function test_aba_iss_traz_a_prefeitura_e_trava_os_campos_dela(): void
    {
        $this->prefeitura();

        $dados = IssDaPrefeitura::dados($this->municipioId);
        $this->assertSame('Prefeita da Prefeitura', $dados['nome_prefeito']);
        $this->assertTrue($dados['cobra_iss']);
        $this->assertEquals(2.5, $dados['aliquota_iss']);
        $this->assertNotContains('cel_prefeito', IssDaPrefeitura::travados($this->municipioId), 'vazio no cadastro continua editavel');

        $plano = $this->plano();
        $this->get(route('pmda.planos.edit', $plano->id))->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p->where('iss_travados', fn ($t) => collect($t)->contains('nome_prefeito')));

        // Servidor ignora o valor travado que vier do formulario.
        $this->put(route('pmda.planos.update', $plano->id), ['nome_prefeito' => 'Outro Nome', 'cel_prefeito' => '31999998888'])
            ->assertSessionHasNoErrors();
        $plano->refresh();
        $this->assertSame('Prefeita da Prefeitura', $plano->nome_prefeito);
        $this->assertSame('31999998888', $plano->cel_prefeito);
        $this->assertSame('Lei 10/2020', $plano->num_lei_iss);
    }

    public function test_sem_prefeitura_cadastrada_nada_fica_travado(): void
    {
        $this->assertSame([], IssDaPrefeitura::travados($this->municipioId));

        $plano = $this->plano();
        $this->put(route('pmda.planos.update', $plano->id), ['nome_prefeito' => 'Digitado'])->assertSessionHasNoErrors();
        $this->assertSame('Digitado', $plano->fresh()->nome_prefeito);
    }

    // ── Listagem: ano e edicao ─────────────────────────────────────────────

    public function test_filtro_por_ano_e_edicao_so_quando_editavel(): void
    {
        $antigo = $this->plano('APROVADO', '2025-03-01');
        $atual = $this->plano('RASCUNHO', '2026-03-01');

        $this->get(route('pmda.planos.index', ['ano' => 2025, 'municipio_id' => $this->municipioId]))->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->where('anos', fn ($anos) => collect($anos)->contains(2025) && collect($anos)->contains(2026))
                ->where('planos.data', fn ($d) => collect($d)->pluck('id')->all() === [$antigo->id])
                ->where('planos.data.0.pode_editar', false));

        $this->get(route('pmda.planos.edit', $antigo->id))->assertRedirect(route('pmda.planos.index'));
        $this->get(route('pmda.planos.edit', $atual->id))->assertOk();
    }

    // ── Distribuicao: ponto de captacao e 1 comunidade por PMDA ────────────

    public function test_comunidade_recebe_ponto_do_plano_e_nao_repete(): void
    {
        $plano = $this->plano();
        $pontoA = $this->ponto('Poco A');
        $pontoB = $this->ponto('Poco B');
        $plano->pontos()->attach([$pontoA, $pontoB]);
        $pontoAlheio = $this->ponto('Poco fora do plano');
        $service = app(ComunidadeService::class);

        // Sem escolha: fallback para o primeiro ponto do plano.
        $c1 = $service->adicionar($plano, ['comunidade_id' => $this->comunidade('Corrego Fundo')]);
        $this->assertSame($pontoA, $c1->ponto_id);

        $c2 = $service->adicionar($plano, ['comunidade_id' => $this->comunidade('Barreiro'), 'ponto_id' => $pontoB]);
        $this->assertSame($pontoB, $c2->ponto_id);

        try {
            $service->adicionar($plano, ['comunidade_id' => $this->comunidade('Lagoa'), 'ponto_id' => $pontoAlheio]);
            $this->fail('ponto de outro plano deveria ser recusado');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('não pertence', $e->getMessage());
        }

        $this->expectException(\DomainException::class);
        $service->adicionar($plano, ['comunidade_id' => $c1->comunidade_id]);
    }

    public function test_comunidade_de_pmda_ativo_nao_entra_em_outro(): void
    {
        $comunidade = $this->comunidade('Sitio Novo');
        app(ComunidadeService::class)->adicionar($this->plano(), ['comunidade_id' => $comunidade]);

        $this->expectExceptionMessage('outro PMDA ativo');
        app(ComunidadeService::class)->adicionar($this->plano(), ['comunidade_id' => $comunidade]);
    }

    // ── Homonimos ──────────────────────────────────────────────────────────

    public function test_olhos_dagua_com_outra_grafia_e_a_mesma_comunidade(): void
    {
        $this->assertTrue(NomeDeLugar::iguais("Olhos d'Água", 'OLHOS DAGUA'));
        $this->assertTrue(NomeDeLugar::iguais("Olhos D'agua", 'olhos d agua'));
        $this->assertFalse(NomeDeLugar::iguais("Olhos d'Água", 'Olhos de Agua Grande'));

        $this->comunidade("Olhos d'Água");

        $this->expectExceptionMessage("Olhos d'Água");
        app(ComunidadeSolicitacaoService::class)->criar($this->plano(), ['nome' => 'Olhos Dagua'], $this->user->id);
    }

    public function test_solicitacao_pendente_com_outra_grafia_e_duplicada(): void
    {
        $plano = $this->plano();
        app(ComunidadeSolicitacaoService::class)->criar($plano, ['nome' => 'São José'], $this->user->id);

        $this->expectExceptionMessage('solicitação pendente');
        app(ComunidadeSolicitacaoService::class)->criar($plano, ['nome' => 'sao jose'], $this->user->id);
    }

    // ── 1 TDAP por municipio ───────────────────────────────────────────────

    public function test_municipio_nao_ativa_segundo_cronograma_tdap(): void
    {
        $agora = ['created_at' => now(), 'updated_at' => now()];
        $ata = DB::table('tdap_atas')->insertGetId(['numero' => 'A56-' . random_int(100, 999), 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'] + $agora);
        $prestador = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'P56', 'email' => 'p@t.local'] + $agora);
        $lote = DB::table('tdap_lotes')->insertGetId(['ata_id' => $ata, 'prestador_id' => $prestador, 'numero' => 'L56', 'qtd_agua_m3' => 10, 'valor_m3' => 1] + $agora);
        $base = ['ata_id' => $ata, 'lote_id' => $lote, 'prestador_id' => $prestador, 'municipio_id' => $this->municipioId, 'dt_inicio' => '2026-10-01', 'dt_final' => '2026-10-31'] + $agora;

        DB::table('tdap_cronogramas')->insert(['numero' => 'CR56-ATIVO', 'ativo' => true] + $base);
        $rascunho = Cronograma::findOrFail(DB::table('tdap_cronogramas')->insertGetId(['numero' => 'CR56-NOVO', 'ativo' => false, 'fase_tramite' => 'aprovado'] + $base));

        [$pode, $motivo] = app(CronogramaService::class)->podeAtivar($rascunho);

        $this->assertFalse($pode);
        $this->assertStringContainsString('CR56-ATIVO', $motivo);
    }
}
