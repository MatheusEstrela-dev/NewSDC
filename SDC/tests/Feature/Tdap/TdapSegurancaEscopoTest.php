<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Correcoes de seguranca do TDAP:
 *  1. consulta do PMDA pelo cronograma so mostra plano aprovado/ligado;
 *  2. parecer "Aprovada" exige tdap.vistorias.aprovar;
 *  3. escrita (viagem, alocacao, cronograma) respeita o municipio de lotacao;
 *  4. CSV neutraliza formula (historico exportado);
 *  5. historico recortado pelo municipio de lotacao.
 */
class TdapSegurancaEscopoTest extends TestCase
{
    use DatabaseTransactions;

    private int $municipioA;
    private int $municipioB;
    private int $ataId;
    private int $loteId;
    private int $prestadorId;
    private int $caminhaoId;
    private int $cronogramaA;
    private int $cronogramaB;
    private int $ccA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $this->municipioA = Municipio::factory()->create()->id;
        $this->municipioB = Municipio::factory()->create()->id;

        $this->ataId = DB::table('tdap_atas')->insertGetId(['numero' => 'SE-'.random_int(1000, 9999), 'dt_inicio' => now()->subMonth()->toDateString(), 'dt_final' => now()->addYear()->toDateString()] + $agora);
        $this->prestadorId = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador SE', 'email' => 'se@t.local'] + $agora);
        $this->loteId = DB::table('tdap_lotes')->insertGetId(['ata_id' => $this->ataId, 'prestador_id' => $this->prestadorId, 'numero' => 'L-SE', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        DB::table('tdap_lote_municipios')->insert([
            ['lote_id' => $this->loteId, 'municipio_id' => $this->municipioA],
            ['lote_id' => $this->loteId, 'municipio_id' => $this->municipioB],
        ]);
        $this->caminhaoId = DB::table('tdap_caminhoes')->insertGetId(['prestador_id' => $this->prestadorId, 'placa' => 'SEC'.random_int(1, 9).'A'.random_int(10, 99), 'capacidade_m3' => 8, 'ativo' => true] + $agora);

        $this->cronogramaA = $this->cronograma($this->municipioA, true);
        $this->cronogramaB = $this->cronograma($this->municipioB, false);
        $this->ccA = DB::table('tdap_crono_caminhoes')->insertGetId(['cronograma_id' => $this->cronogramaA, 'caminhao_id' => $this->caminhaoId] + $agora);
    }

    private function cronograma(int $municipioId, bool $ativo): int
    {
        return DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-SE-'.random_int(10000, 99999), 'ata_id' => $this->ataId, 'lote_id' => $this->loteId,
            'municipio_id' => $municipioId, 'prestador_id' => $this->prestadorId,
            'dt_inicio' => now()->subWeek()->toDateString(), 'dt_final' => now()->addMonth()->toDateString(),
            'ativo' => $ativo, 'ativado_em' => $ativo ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Usuario com exatamente estas permissoes. Com $municipioId, lotado num
     * COMPDEC dele (escopo municipal); sem, estadual.
     *
     * @param  list<string>  $permissoes
     */
    private function usuario(array $permissoes, ?int $municipioId = null): User
    {
        foreach ($permissoes as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Estadual e quem esta lotado num orgao sem municipio (CEDEC); usuario
        // sem orgao nenhum nao le nada (EscopoDeLeitura::SEM_ESCOPO).
        $dados = ['orgao_principal_id' => $municipioId !== null
            ? Orgao::factory()->compdec()->create(['municipio_id' => $municipioId])->id
            : Orgao::factory()->cedec()->create()->id];
        $user = User::factory()->create($dados);
        $user->givePermissionTo($permissoes);

        return $user->fresh();
    }

    private function planoPmda(int $municipioId, string $status): int
    {
        return DB::table('pmda_planos')->insertGetId([
            'municipio_id' => $municipioId,
            'status'       => $status,
            'data_aprov'   => in_array($status, ['APROVADO', 'ATENDIDO'], true) ? now()->toDateString() : null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function ponto(int $municipioId): int
    {
        return DB::table('pip_pmda_ponto')->insertGetId([
            'municipio_id' => $municipioId, 'nome' => 'Ponto SE '.random_int(100, 999), 'tipo' => 1, 'ativo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function historico(string $entityType, int $entityId, string $obs): int
    {
        return DB::table('tdap_historicos')->insertGetId([
            'data_evento' => now(), 'tipo_evento' => 'teste.seguranca', 'entity_type' => $entityType,
            'entity_id' => $entityId, 'obs' => $obs, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- 1. PMDA

    private function urlPmda(?int $planoId): string
    {
        return route('tdap.cronogramas.pmda', array_filter([
            'municipio_id' => $this->municipioA,
            'pmda_plano_id' => $planoId,
        ]));
    }

    public function test_pmda_em_rascunho_por_id_e_404_so_com_permissao_do_tdap(): void
    {
        $rascunho = $this->planoPmda($this->municipioA, 'RASCUNHO');
        $this->actingAs($this->usuario(['tdap.cronogramas.view']));

        $this->get($this->urlPmda($rascunho))->assertNotFound();
    }

    public function test_pmda_em_rascunho_abre_para_quem_ve_o_plano_no_pmda(): void
    {
        $rascunho = $this->planoPmda($this->municipioA, 'RASCUNHO');
        $this->actingAs($this->usuario(['tdap.cronogramas.view', 'pmda.planos.view']));

        $this->get($this->urlPmda($rascunho))->assertOk();
    }

    public function test_pmda_aprovado_por_id_abre_com_permissao_do_tdap(): void
    {
        $aprovado = $this->planoPmda($this->municipioA, 'APROVADO');
        $this->actingAs($this->usuario(['tdap.cronogramas.view']));

        $this->get($this->urlPmda($aprovado))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('plano.id', $aprovado));
    }

    public function test_pmda_nao_aprovado_ligado_a_cronograma_do_municipio_abre(): void
    {
        $emAnalise = $this->planoPmda($this->municipioA, 'EM_ANALISE');
        DB::table('tdap_cronograma_ponto')->insert([
            'cronograma_id' => $this->cronogramaA, 'ponto_id' => $this->ponto($this->municipioA), 'pmda_plano_id' => $emAnalise,
        ]);
        $this->actingAs($this->usuario(['tdap.cronogramas.view']));

        $this->get($this->urlPmda($emAnalise))->assertOk();
    }

    public function test_pmda_sem_id_mostra_so_o_vigente(): void
    {
        $this->planoPmda($this->municipioA, 'RASCUNHO');
        $this->actingAs($this->usuario(['tdap.cronogramas.view']));

        $this->get($this->urlPmda(null))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('plano', null));
    }

    // ------------------------------------------------------- 2. Vistoria aprovada

    /** @return array<string, mixed> */
    private function ficha(string $parecer): array
    {
        return [
            'nome' => 'Vistoria SE', 'data' => now()->subDay()->toDateString(),
            'capacidade' => 8, 'parecer' => $parecer,
        ];
    }

    public function test_vistoria_aprovada_sem_permissao_de_aprovar_e_recusada(): void
    {
        $this->actingAs($this->usuario(['tdap.vistorias.create']));

        $this->post(route('tdap.frota.vistorias.store', $this->caminhaoId), $this->ficha('aprovada'))
            ->assertSessionHasErrors('parecer');
        $this->assertDatabaseMissing('tdap_vistorias', ['placa_id' => $this->caminhaoId, 'nome' => 'Vistoria SE']);
    }

    public function test_vistoria_reprovada_nao_exige_permissao_de_aprovar(): void
    {
        $this->actingAs($this->usuario(['tdap.vistorias.create']));

        $this->post(route('tdap.frota.vistorias.store', $this->caminhaoId), $this->ficha('reprovada'))
            ->assertSessionDoesntHaveErrors('parecer');
    }

    public function test_vistoria_aprovada_com_permissao_de_aprovar_passa(): void
    {
        $this->actingAs($this->usuario(['tdap.vistorias.create', 'tdap.vistorias.aprovar']));

        $this->post(route('tdap.frota.vistorias.store', $this->caminhaoId), $this->ficha('aprovada'))
            ->assertSessionDoesntHaveErrors('parecer');
    }

    private function vistoria(string $parecer): int
    {
        return DB::table('tdap_vistorias')->insertGetId([
            'nome' => 'Vistoria existente', 'placa_id' => $this->caminhaoId, 'data' => now()->subDays(2)->toDateString(),
            'capacidade' => 8, 'parecer' => $parecer, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_edicao_que_muda_parecer_para_aprovada_exige_permissao(): void
    {
        $id = $this->vistoria('reprovada');
        $this->actingAs($this->usuario(['tdap.vistorias.edit']));

        $this->put(route('tdap.frota.vistorias.update', $id), $this->ficha('aprovada') + ['placa_id' => $this->caminhaoId])
            ->assertSessionHasErrors('parecer');
        $this->assertDatabaseHas('tdap_vistorias', ['id' => $id, 'parecer' => 'reprovada']);
    }

    public function test_edicao_que_mantem_parecer_aprovado_nao_exige_permissao(): void
    {
        $id = $this->vistoria('aprovada');
        $this->actingAs($this->usuario(['tdap.vistorias.edit']));

        $this->put(route('tdap.frota.vistorias.update', $id), $this->ficha('aprovada') + ['placa_id' => $this->caminhaoId])
            ->assertSessionDoesntHaveErrors('parecer');
    }

    public function test_caminhao_novo_com_vistoria_aprovada_exige_permissao(): void
    {
        $this->actingAs($this->usuario(['tdap.vistorias.create', 'tdap.caminhoes.create']));

        $this->post(route('tdap.frota.vistorias.store-nova'), $this->ficha('aprovada') + [
            'prestador_id' => $this->prestadorId, 'placa' => 'SEN'.random_int(1, 9).'B'.random_int(10, 99),
        ])->assertSessionHasErrors('parecer');
    }

    // -------------------------------------------------- 3. Escopo na escrita

    public function test_viagem_em_cronograma_de_outro_municipio_e_403(): void
    {
        $this->actingAs($this->usuario(['tdap.viagens.create'], $this->municipioB));

        $this->post(route('tdap.viagens.store'), [
            'crono_caminhao_id' => $this->ccA, 'data_registro' => now()->toDateString(),
        ])->assertForbidden();
    }

    public function test_viagem_no_proprio_municipio_nao_e_barrada_pelo_escopo(): void
    {
        $this->actingAs($this->usuario(['tdap.viagens.create'], $this->municipioA));

        $resposta = $this->post(route('tdap.viagens.store'), [
            'crono_caminhao_id' => $this->ccA, 'data_registro' => now()->toDateString(),
        ]);

        $this->assertNotSame(403, $resposta->getStatusCode());
    }

    public function test_alocacao_em_cronograma_de_outro_municipio_e_403(): void
    {
        $user = $this->usuario(['tdap.cronogramas.edit'], $this->municipioB);
        $this->actingAs($user);
        $payload = ['agua_prevista' => 10, 'num_viagens' => 2];

        $this->post(route('tdap.crono_caminhoes.store'), $payload + [
            'cronograma_id' => $this->cronogramaA, 'caminhao_id' => $this->caminhaoId,
        ])->assertForbidden();

        $this->put(route('tdap.crono_caminhoes.update', $this->ccA), $payload)->assertForbidden();
        $this->delete(route('tdap.crono_caminhoes.destroy', $this->ccA))->assertForbidden();
        $this->assertDatabaseHas('tdap_crono_caminhoes', ['id' => $this->ccA, 'deleted_at' => null]);
    }

    /** @return array<string, mixed> */
    private function payloadCronograma(int $municipioId): array
    {
        return [
            'numero' => 'CR-SEN-'.random_int(1000, 9999), 'ata_id' => $this->ataId, 'lote_id' => $this->loteId,
            'municipio_id' => $municipioId, 'prestador_id' => $this->prestadorId,
            'consumo_diario' => 10, 'dias' => 10,
            'dt_inicio' => now()->toDateString(), 'dt_final' => now()->addMonth()->toDateString(),
            'ponto_captacao_ids' => [$this->ponto($municipioId)],
        ];
    }

    public function test_cronograma_novo_para_outro_municipio_e_403(): void
    {
        config(['tdap.ponto_captacao.exige_pmda_aprovado' => false]);
        $this->actingAs($this->usuario(['tdap.cronogramas.create'], $this->municipioB));

        $payload = $this->payloadCronograma($this->municipioA);
        $this->post(route('tdap.cronogramas.store'), $payload)->assertForbidden();
        $this->assertDatabaseMissing('tdap_cronogramas', ['numero' => $payload['numero']]);
    }

    public function test_cronograma_proprio_nao_pode_ser_movido_para_outro_municipio(): void
    {
        config(['tdap.ponto_captacao.exige_pmda_aprovado' => false]);
        $this->actingAs($this->usuario(['tdap.cronogramas.edit'], $this->municipioB));

        $this->put(route('tdap.cronogramas.update', $this->cronogramaB), $this->payloadCronograma($this->municipioA))
            ->assertForbidden();
        $this->assertDatabaseHas('tdap_cronogramas', ['id' => $this->cronogramaB, 'municipio_id' => $this->municipioB]);
    }

    // ------------------------------------------------------ 5. Historico

    public function test_historico_de_quem_e_lotado_mostra_so_o_proprio_municipio(): void
    {
        $doA = $this->historico('cronograma', $this->cronogramaA, 'evento A');
        $this->historico('cronograma', $this->cronogramaB, 'evento B');
        $this->historico('ata', $this->ataId, 'evento ata');
        $daViagemA = $this->historico('crono_caminhao', $this->ccA, 'alocacao A');

        $this->actingAs($this->usuario(['tdap.historico.view'], $this->municipioA));

        $this->get(route('tdap.historicos.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('historicos.data', 2)
                ->where('historicos.data', fn ($linhas) => collect($linhas)->pluck('id')->sort()->values()->all() === collect([$doA, $daViagemA])->sort()->values()->all()));
    }

    public function test_historico_de_outro_municipio_por_id_e_403(): void
    {
        $doA = $this->historico('cronograma', $this->cronogramaA, 'evento A');
        $doB = $this->historico('cronograma', $this->cronogramaB, 'evento B');
        $this->actingAs($this->usuario(['tdap.historico.view'], $this->municipioA));

        $this->get(route('tdap.historicos.show', $doB))->assertForbidden();
        $this->get(route('tdap.historicos.show', $doA))->assertOk();
    }

    public function test_historico_por_entidade_nao_devolve_outro_municipio(): void
    {
        $this->historico('cronograma', $this->cronogramaB, 'evento B');
        $this->actingAs($this->usuario(['tdap.historico.view'], $this->municipioA));

        $this->getJson(route('tdap.historicos.por_entidade', ['entity_type' => 'cronograma', 'entity_id' => $this->cronogramaB]))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_historico_estadual_continua_vendo_todos_os_municipios(): void
    {
        $this->historico('cronograma', $this->cronogramaB, 'evento B');
        $this->actingAs($this->usuario(['tdap.historico.view']));

        $this->getJson(route('tdap.historicos.por_entidade', ['entity_type' => 'cronograma', 'entity_id' => $this->cronogramaB]))
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_export_do_historico_recorta_municipio_e_neutraliza_formula(): void
    {
        $this->historico('cronograma', $this->cronogramaA, '=SOMA(1;1)');
        $this->historico('cronograma', $this->cronogramaB, 'evento de B');
        $this->actingAs($this->usuario(['tdap.historico.view'], $this->municipioA));

        $csv = $this->get(route('tdap.historicos.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString("'=SOMA(1;1)", $csv);
        $this->assertStringNotContainsString('evento de B', $csv);
    }
}
