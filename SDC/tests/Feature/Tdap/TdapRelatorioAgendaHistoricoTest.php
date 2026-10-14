<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Services\HistoricoService;
use App\Modules\Tdap\Support\VigenciaVistoria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * #62: relatorio do cronograma nas tres perspectivas, agenda do vistoriador
 * e historico com a entidade legivel (sem o `tipo#id` legado).
 */
class TdapRelatorioAgendaHistoricoTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private int $cronogramaId;
    private int $caminhaoId;
    private string $placa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->user = $this->user->fresh();
        $this->actingAs($this->user);

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $municipio = Municipio::factory()->create()->id;
        $ata = DB::table('tdap_atas')->insertGetId(['numero' => 'RA-' . random_int(1000, 9999), 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'] + $agora);
        $prestador = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador Rel', 'email' => 'r@t.local'] + $agora);
        $lote = DB::table('tdap_lotes')->insertGetId(['ata_id' => $ata, 'prestador_id' => $prestador, 'numero' => 'L-REL', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        $this->placa = 'REL' . random_int(1, 9) . 'A' . random_int(10, 99);
        $this->caminhaoId = DB::table('tdap_caminhoes')->insertGetId(['prestador_id' => $prestador, 'placa' => $this->placa, 'capacidade_m3' => 8, 'ativo' => true] + $agora);
        $this->cronogramaId = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-REL-' . random_int(100, 999), 'ata_id' => $ata, 'lote_id' => $lote, 'municipio_id' => $municipio,
            'prestador_id' => $prestador, 'dt_inicio' => '2026-10-01', 'dt_final' => '2026-10-31',
        ] + $agora);
        $cc = DB::table('tdap_crono_caminhoes')->insertGetId(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $this->caminhaoId] + $agora);

        // 2 aprovadas no dia 05 (uma confirmada pelo municipio), 1 pendente no dia 06.
        DB::table('tdap_crono_viagens')->insert([
            ['crono_caminhao_id' => $cc, 'data_registro' => '2026-10-05 08:00:00', 'validado' => 1, 'confirmado' => 1] + $agora,
            ['crono_caminhao_id' => $cc, 'data_registro' => '2026-10-05 14:00:00', 'validado' => 1, 'confirmado' => null] + $agora,
            ['crono_caminhao_id' => $cc, 'data_registro' => '2026-10-06 09:00:00', 'validado' => null, 'confirmado' => null] + $agora,
        ]);
    }

    public function test_relatorio_cedec_traz_execucao_e_valor(): void
    {
        $r = $this->getJson(route('tdap.cronogramas.relatorio', ['cronograma' => $this->cronogramaId, 'perspectiva' => 'cedec']))
            ->assertOk()->json('relatorio');

        $this->assertSame('cedec', $r['perspectiva']);
        $this->assertSame(3, $r['resumo']['total']);
        $this->assertSame(2, $r['resumo']['aprovadas']);
        $this->assertSame(1, $r['resumo']['confirmadas']);
        $this->assertEquals(16.0, $r['resumo']['m3_aprovado']);
        $this->assertEquals(160.0, $r['resumo']['valor_aprovado']); // 16 m3 x R$ 10
        $this->assertCount(2, $r['diario']);
        $this->assertSame(2, $r['diario'][0]['viagens']);
        $this->assertCount(3, $r['viagens']);
    }

    public function test_relatorio_prestador_nao_expoe_valor_e_copasa_nao_lista_viagens(): void
    {
        $prestador = $this->getJson(route('tdap.cronogramas.relatorio', ['cronograma' => $this->cronogramaId, 'perspectiva' => 'prestador']))
            ->assertOk()->json('relatorio');
        $this->assertArrayNotHasKey('valor_aprovado', $prestador['resumo']);
        $this->assertCount(3, $prestador['viagens']);
        $this->assertSame('confirmada', $prestador['viagens'][0]['status_confirmacao']);

        $copasa = $this->getJson(route('tdap.cronogramas.relatorio', ['cronograma' => $this->cronogramaId, 'perspectiva' => 'copasa']))
            ->assertOk()->json('relatorio');
        $this->assertSame([], $copasa['viagens']);
        $this->assertArrayNotHasKey('valor_aprovado', $copasa['resumo']);
        $this->assertEquals(8.0, $copasa['diario'][1]['m3']);
    }

    public function test_relatorio_recusa_perspectiva_desconhecida(): void
    {
        $this->getJson(route('tdap.cronogramas.relatorio', ['cronograma' => $this->cronogramaId, 'perspectiva' => 'outra']))
            ->assertStatus(422);
    }

    public function test_agenda_mostra_vistoria_vencimento_e_revistoria(): void
    {
        // Vistoria aprovada ha 11 meses e 20 dias: vence em ~10 dias.
        $data = now()->subMonthsNoOverflow(12)->addDays(10)->toDateString();
        DB::table('tdap_vistorias')->insert([
            'nome' => 'Vistoria agenda', 'placa_id' => $this->caminhaoId, 'data' => $data, 'capacidade' => 8,
            'parecer' => 'aprovada', 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $vence = VigenciaVistoria::validoAte($data);

        $this->get(route('tdap.frota.vistorias.agenda', ['ano' => $vence->year, 'mes' => $vence->month]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tdap/Frota/Vistorias/Agenda')
                ->where('eventos', fn ($eventos) => collect($eventos)->contains(fn ($e) => $e['id'] === "vencimento-{$this->caminhaoId}" && $e['start'] === $vence->toDateString()))
                ->where('revistorias', fn ($r) => collect($r)->contains(fn ($x) => $x['caminhao']['id'] === $this->caminhaoId && $x['dias'] >= 0 && $x['dias'] <= 30)));

        $mesDaVistoria = \Illuminate\Support\Carbon::parse($data);
        $this->get(route('tdap.frota.vistorias.agenda', ['ano' => $mesDaVistoria->year, 'mes' => $mesDaVistoria->month, 'minhas' => 1]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('minhas', true)
                ->where('eventos', fn ($eventos) => collect($eventos)->contains(fn ($e) => ($e['extendedProps']['tipo'] ?? null) === 'vistoria' && $e['extendedProps']['ehMinha'] === true)));
    }

    public function test_historico_mostra_entidade_legivel_com_link(): void
    {
        $cronograma = Cronograma::findOrFail($this->cronogramaId);
        app(HistoricoService::class)->registrar('cronograma.pontos_alterados', $cronograma, 'teste');

        $this->get(route('tdap.historicos.index', ['entity_type' => 'cronograma', 'entity_id' => $this->cronogramaId]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('historicos.data.0.tipo_rotulo', 'Cronograma pontos alterados')
                ->where('historicos.data.0.entidade.rotulo', "Cronograma {$cronograma->numero}")
                ->where('historicos.data.0.entidade.url', route('tdap.cronogramas.show', $this->cronogramaId))
                ->where('tiposEvento', fn ($t) => collect($t)->contains('value', 'cronograma.pontos_alterados')));

        // Registro excluido: o evento continua, sem link e marcado.
        $cronograma->delete();
        $this->get(route('tdap.historicos.index', ['entity_type' => 'cronograma', 'entity_id' => $this->cronogramaId]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('historicos.data.0.entidade.url', null)
                ->where('historicos.data.0.entidade.excluido', true));
    }
}
