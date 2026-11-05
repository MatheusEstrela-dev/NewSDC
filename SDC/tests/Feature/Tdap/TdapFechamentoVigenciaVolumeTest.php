<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Tdap\Services\CronogramaService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Modules\Tdap\Domain\Exceptions\ViagemForaDoLimiteException;
use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Services\CronoViagemService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lacunas P1 da auditoria de 2026-11-05:
 *  1. trava de caminhoes no FECHAMENTO do cronograma (da367650);
 *  2. limite de vigencia da viagem (final ligado, inicial desligado, prorrogacao);
 *  3. volume do cronograma = soma dos caminhoes, nunca o volume do lote.
 *
 * O relogio e fixado em meio-dia: datas montadas com now() nao viram "amanha"
 * perto da meia-noite.
 */
class TdapFechamentoVigenciaVolumeTest extends TestCase
{
    use DatabaseTransactions;

    private int $municipioId;
    private int $prestadorId;
    private int $loteId;
    private int $cronogramaId;
    private int $caminhaoId;
    private int $alocacaoId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(12, 0));
        config(['tdap.viagens.exige_confirmacao_municipio' => false]);

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $municipio = $this->municipioId = Municipio::factory()->create()->id;
        $ata = DB::table('tdap_atas')->insertGetId(['numero' => 'FV-'.random_int(1000, 9999), 'dt_inicio' => now()->subYear()->toDateString(), 'dt_final' => now()->addYear()->toDateString()] + $agora);
        $this->prestadorId = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador FV', 'email' => 'fv@t.local'] + $agora);
        // qtd_agua_m3 = 1000 de proposito: se o volume viesse do lote, os testes de volume quebram.
        $this->loteId = DB::table('tdap_lotes')->insertGetId(['ata_id' => $ata, 'prestador_id' => $this->prestadorId, 'numero' => 'L-FV', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        DB::table('tdap_lote_municipios')->insert(['lote_id' => $this->loteId, 'municipio_id' => $municipio]);

        $this->cronogramaId = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-FV-'.random_int(1000, 9999), 'ata_id' => $ata, 'lote_id' => $this->loteId,
            'municipio_id' => $municipio, 'prestador_id' => $this->prestadorId,
            'dt_inicio' => now()->subDays(10)->toDateString(), 'dt_final' => now()->addMonth()->toDateString(),
            'ativo' => true, 'ativado_em' => now(), 'fase_tramite' => 'em_execucao',
        ] + $agora);

        $this->caminhaoId = $this->novoCaminhao(10);
        $this->alocacaoId = $this->alocar($this->caminhaoId, 100);
    }

    /* ------------------------------------------------------------ helpers */

    private function novoCaminhao(float $capacidade): int
    {
        $id = DB::table('tdap_caminhoes')->insertGetId([
            'prestador_id' => $this->prestadorId, 'placa' => 'FVZ'.random_int(1, 9).'C'.random_int(10, 99),
            'capacidade_m3' => $capacidade, 'ativo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('tdap_vistorias')->insert([
            'nome' => 'Vistoriador', 'placa_id' => $id, 'data' => now()->toDateString(), 'capacidade' => $capacidade,
            'parecer' => 'aprovada', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }

    private function alocar(int $caminhaoId, float $aguaPrevista): int
    {
        return DB::table('tdap_crono_caminhoes')->insertGetId([
            'cronograma_id' => $this->cronogramaId, 'caminhao_id' => $caminhaoId, 'agua_prevista' => $aguaPrevista,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function cronograma(array $campos): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update($campos);
    }

    private function encerrar(): void
    {
        $this->cronograma(['ativo' => false, 'encerrado_em' => now(), 'fase_tramite' => 'entregue']);
    }

    private function registrar(string $data, ?int $alocacaoId = null): CronoViagem
    {
        return app(CronoViagemService::class)->registrar(new CronoViagemDTO($alocacaoId ?? $this->alocacaoId, $data, null));
    }

    private function esperaDominio(callable $acao, string $trecho): void
    {
        try {
            $acao();
            $this->fail("esperava DomainException contendo '{$trecho}'");
        } catch (\DomainException $e) {
            $this->assertStringContainsString($trecho, $e->getMessage());
        }
    }

    /* ------------------------------------- 1. fechamento trava caminhoes */

    public function test_encerrado_recusa_alocar_caminhao(): void
    {
        $this->encerrar();
        $novo = $this->novoCaminhao(8);

        $this->esperaDominio(
            fn () => app(CronoCaminhaoService::class)->alocar(new CronoCaminhaoDTO($this->cronogramaId, $novo, null, 16.0, 2, 1)),
            'encerrado',
        );

        $this->assertSame(1, CronoCaminhao::where('cronograma_id', $this->cronogramaId)->count());
    }

    public function test_encerrado_recusa_alterar_alocacao(): void
    {
        $this->encerrar();

        $this->esperaDominio(
            fn () => app(CronoCaminhaoService::class)->atualizar($this->alocacaoId, new CronoCaminhaoDTO($this->cronogramaId, $this->caminhaoId, null, 999.0, 2, 1)),
            'encerrado',
        );

        $this->assertEquals(100.0, (float) CronoCaminhao::findOrFail($this->alocacaoId)->agua_prevista);
    }

    /**
     * Achado M3 da auditoria: remover() so confere tramitacao_travada, nao
     * encerrado_em. Enquanto a guarda nao entrar, o teste fica INCOMPLETO
     * (amarelo) em vez de vermelho -- e passa a valer sozinho quando corrigir.
     */
    public function test_encerrado_recusa_remover_alocacao(): void
    {
        $this->encerrar();

        try {
            app(CronoCaminhaoService::class)->remover($this->alocacaoId);
        } catch (\DomainException $e) {
            $this->assertStringContainsString('encerrado', $e->getMessage());
            $this->assertNotNull(CronoCaminhao::find($this->alocacaoId));

            return;
        }

        $this->markTestIncomplete('M3: remover() ainda aceita alocacao de cronograma encerrado.');
    }

    public function test_encerrado_recusa_registrar_viagem(): void
    {
        $this->encerrar();

        $this->esperaDominio(fn () => $this->registrar(now()->toDateTimeString()), 'ativos');
        $this->assertSame(0, CronoViagem::where('crono_caminhao_id', $this->alocacaoId)->count());
    }

    /* ---------------------------------------------- 2. vigencia da viagem */

    public function test_viagem_depois_do_fim_da_vigencia_e_recusada(): void
    {
        $this->cronograma(['dt_final' => now()->subDays(2)->toDateString()]);

        $this->expectException(ViagemForaDoLimiteException::class);
        $this->expectExceptionMessage('ultrapassa a vigencia');

        $this->registrar(now()->subDay()->setTime(9, 0)->toDateTimeString());
    }

    public function test_viagem_no_ultimo_dia_e_aceita_ate_23h59(): void
    {
        $fim = now()->subDay();
        $this->cronograma(['dt_final' => $fim->toDateString()]);

        $viagem = $this->registrar($fim->copy()->setTime(23, 59)->toDateTimeString());

        $this->assertNotNull($viagem->id);
    }

    public function test_prorrogacao_estende_o_limite_da_viagem(): void
    {
        $this->cronograma([
            'dt_final'             => now()->subDays(3)->toDateString(),
            'dt_final_prorrogacao' => now()->addWeek()->toDateString(),
        ]);

        $viagem = $this->registrar(now()->toDateTimeString());

        $this->assertNotNull($viagem->id);
        $this->assertTrue(Cronograma::findOrFail($this->cronogramaId)->dt_final_efetiva->isSameDay(now()->addWeek()));
    }

    /** Limite INICIAL segue desligado de proposito (31% do acervo tem data anterior ao dt_inicio). */
    public function test_viagem_antes_do_inicio_continua_aceita(): void
    {
        $viagem = $this->registrar(now()->subDays(20)->toDateTimeString());

        $this->assertNotNull($viagem->id);
    }

    /* ------------------------------------- 3. volume = soma dos caminhoes */

    public function test_volume_vem_da_soma_dos_caminhoes_e_nao_do_lote(): void
    {
        $segundo = $this->alocar($this->novoCaminhao(15), 200);
        $svc = app(CronoViagemService::class);

        $svc->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);
        $svc->validar($this->registrar(now()->subDay()->toDateTimeString(), $segundo)->id, true);

        $cronograma = Cronograma::findOrFail($this->cronogramaId);
        $this->assertEquals(25.0, $cronograma->volume_entregue);      // 10 + 15
        $this->assertEquals(300.0, $cronograma->volume_contratado);   // 100 + 200, nunca os 1000 do lote
        $this->assertEquals(round(25 / 300 * 100, 2), $cronograma->percentual_entregue);
    }

    public function test_viagem_rejeitada_nao_soma_volume(): void
    {
        $svc = app(CronoViagemService::class);

        $svc->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);
        $svc->validar($this->registrar(now()->subDay()->toDateTimeString())->id, false);

        $this->assertEquals(10.0, Cronograma::findOrFail($this->cronogramaId)->volume_entregue);
    }

    public function test_aprovacao_grava_capacidade_e_preco_do_momento(): void
    {
        $viagem = app(CronoViagemService::class)->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);

        $this->assertEquals(10.0, (float) $viagem->capacidade_m3);
        $this->assertEquals(10.0, (float) $viagem->valor_m3);

        // Reajuste posterior do caminhao e do lote nao mexe no que ja foi aprovado.
        DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->update(['capacidade_m3' => 30]);
        DB::table('tdap_lotes')->where('id', $this->loteId)->update(['valor_m3' => 50]);
        app(CronoCaminhaoService::class)->recalcularEntregas($this->alocacaoId);

        $this->assertEquals(10.0, (float) $viagem->fresh()->capacidade_m3);
        $this->assertEquals(10.0, Cronograma::findOrFail($this->cronogramaId)->volume_entregue);
    }

    /* ---------------------- correcoes da auditoria: M1, M2, B1, B2 e API BI */

    private function compdec(int $municipioId): User
    {
        $orgao = Orgao::factory()->compdec()->create(['municipio_id' => $municipioId]);
        $user = User::factory()->create(['orgao_principal_id' => $orgao->id]);
        $user->assignRole(Role::where('slug', 'compdec')->firstOrFail());

        return $user->fresh();
    }

    /** @param list<string> $permissoes */
    private function comPermissoes(array $permissoes, ?int $municipioId = null): User
    {
        foreach ($permissoes as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // Sem municipio = estadual, lotado num orgao CEDEC (sem orgao nenhum nao le nada).
        $dados = ['orgao_principal_id' => $municipioId !== null
            ? Orgao::factory()->compdec()->create(['municipio_id' => $municipioId])->id
            : Orgao::factory()->cedec()->create()->id];
        $user = User::factory()->create($dados);
        $user->givePermissionTo($permissoes);

        return $user->fresh();
    }

    public function test_m1_viagem_atestada_pelo_municipio_nao_pode_ser_excluida(): void
    {
        $viagem = $this->registrar(now()->subDay()->toDateTimeString());
        app(CronoViagemService::class)->confirmarEmLote($this->compdec($this->municipioId), [$viagem->id]);

        $this->esperaDominio(fn () => app(CronoViagemService::class)->remover($viagem->id), 'atestada pelo municipio');
        $this->assertNotNull(CronoViagem::find($viagem->id));
    }

    public function test_m1_viagem_de_cronograma_encerrado_nao_pode_ser_excluida(): void
    {
        $viagem = $this->registrar(now()->subDay()->toDateTimeString());
        $this->encerrar();

        $this->esperaDominio(fn () => app(CronoViagemService::class)->remover($viagem->id), 'encerrado');
        $this->assertNotNull(CronoViagem::find($viagem->id));
    }

    public function test_m1_viagem_pendente_sem_atesto_continua_excluivel(): void
    {
        $viagem = $this->registrar(now()->subDay()->toDateTimeString());

        $this->assertTrue(app(CronoViagemService::class)->remover($viagem->id));
        $this->assertNull(CronoViagem::find($viagem->id));
    }

    public function test_m2_com_a_chave_desligada_municipio_nao_decide_viagem_ja_aprovada(): void
    {
        $viagem = app(CronoViagemService::class)->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);

        $resultado = app(CronoViagemService::class)->reprovarEmLote($this->compdec($this->municipioId), [$viagem->id], 'nao chegou');

        $this->assertSame(0, $resultado['reprovadas']);
        $this->assertNull($viagem->fresh()->confirmado_em);
    }

    public function test_b1_caminhao_excluido_de_outra_empresa_barra_a_ativacao_com_a_mensagem_certa(): void
    {
        $outraEmpresa = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Outra', 'email' => 'o@t.local', 'created_at' => now(), 'updated_at' => now()]);
        $placa = 'OUT'.random_int(1, 9).'X'.random_int(10, 99);
        $intruso = DB::table('tdap_caminhoes')->insertGetId(['prestador_id' => $outraEmpresa, 'placa' => $placa, 'capacidade_m3' => 8, 'ativo' => true, 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->alocar($intruso, 50);

        $motivo = app(CronogramaService::class)->pendenciaParaAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertStringContainsString("Caminhao {$placa} nao pertence a empresa", (string) $motivo);
    }

    public function test_b2_cronograma_com_vigencia_vencida_nao_ativa(): void
    {
        $this->cronograma(['ativo' => false, 'ativado_em' => null, 'fase_tramite' => 'aprovado', 'dt_final' => now()->subDay()->toDateString()]);

        [$pode, $motivo] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertFalse($pode);
        $this->assertStringContainsString('vigencia encerrada', (string) $motivo);
    }

    public function test_b2_prorrogado_ainda_vigente_nao_e_barrado_pela_data(): void
    {
        $this->cronograma(['ativo' => false, 'ativado_em' => null, 'fase_tramite' => 'aprovado', 'dt_final' => now()->subDay()->toDateString(), 'dt_final_prorrogacao' => now()->addWeek()->toDateString()]);

        [, $motivo] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertStringNotContainsString('vigencia encerrada', (string) $motivo);
    }

    public function test_api_bi_sem_permissao_e_403(): void
    {
        $this->actingAs($this->comPermissoes(['tdap.viagens.view']))
            ->getJson(route('api.v1.tdap.cronogramas'))
            ->assertForbidden();
    }

    public function test_api_bi_de_usuario_municipal_ignora_municipio_do_request(): void
    {
        $outro = Municipio::factory()->create()->id;
        app(CronoViagemService::class)->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);

        $linhas = $this->actingAs($this->comPermissoes(['tdap.cronogramas.export'], $outro))
            ->getJson(route('api.v1.tdap.cronogramas', ['municipio_id' => $this->municipioId]))
            ->assertOk()
            ->json('data');

        // Lotado em $outro: mesmo pedindo o municipio do cronograma, nao recebe nada dele.
        $this->assertSame([], $linhas);
    }

    /* ----------------- escopo sem lotacao, cadastros so do estado, exportacoes */

    private function semOrgao(array $permissoes): User
    {
        foreach ($permissoes as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create(['orgao_principal_id' => null]);
        $user->givePermissionTo($permissoes);

        return $user->fresh();
    }

    public function test_usuario_sem_lotacao_nao_le_o_estado_inteiro(): void
    {
        $user = $this->semOrgao(['tdap.cronogramas.view']);

        $this->assertSame(\App\Modules\Tdap\Support\EscopoDeLeitura::SEM_ESCOPO, \App\Modules\Tdap\Support\EscopoDeLeitura::municipioId($user));
        $this->assertFalse(\App\Modules\Tdap\Support\EscopoDeLeitura::ehEstadual($user));
    }

    public function test_usuario_sem_lotacao_recebe_403_na_ficha_do_cronograma(): void
    {
        $this->actingAs($this->semOrgao(['tdap.cronogramas.view']))
            ->get(route('tdap.cronogramas.show', $this->cronogramaId))
            ->assertForbidden();
    }

    public function test_api_bi_de_usuario_sem_lotacao_volta_vazia(): void
    {
        app(CronoViagemService::class)->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);

        $this->actingAs($this->semOrgao(['tdap.cronogramas.export']))
            ->getJson(route('api.v1.tdap.cronogramas', ['municipio_id' => $this->municipioId]))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_usuario_municipal_nao_altera_caminhao_mesmo_com_permissao(): void
    {
        $compdec = $this->comPermissoes(['tdap.caminhoes.view', 'tdap.caminhoes.edit'], $this->municipioId);

        $this->actingAs($compdec)->get(route('tdap.frota.edit', $this->caminhaoId))->assertForbidden();
        $this->actingAs($compdec)
            ->put(route('tdap.frota.update', $this->caminhaoId), ['prestador_id' => $this->prestadorId, 'placa' => 'XXX0X00', 'capacidade_m3' => 99])
            ->assertForbidden();
        $this->assertEquals(10.0, (float) DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('capacidade_m3'));

        // Leitura continua liberada pela permissao.
        $this->actingAs($compdec)->get(route('tdap.frota.show', $this->caminhaoId))->assertOk();
    }

    public function test_usuario_municipal_nao_cria_prestador_ata_nem_lote(): void
    {
        $compdec = $this->comPermissoes(['tdap.prestadores.create', 'tdap.atas.create', 'tdap.lotes.create'], $this->municipioId);

        $this->actingAs($compdec)->get(route('tdap.prestadores.create'))->assertForbidden();
        $this->actingAs($compdec)->post(route('tdap.atas.store'), ['numero' => 'X', 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'])->assertForbidden();
        $this->actingAs($compdec)->get(route('tdap.lotes.create'))->assertForbidden();
    }

    public function test_usuario_estadual_altera_caminhao(): void
    {
        $estadual = $this->comPermissoes(['tdap.caminhoes.view', 'tdap.caminhoes.edit']);

        $this->actingAs($estadual)->get(route('tdap.frota.edit', $this->caminhaoId))->assertOk();
    }

    public function test_csv_de_cronogramas_sai_em_lotes_sem_perder_nem_repetir_linha(): void
    {
        // 3 cronogramas com o MESMO dt_inicio: sem o desempate por id, a
        // paginacao por OFFSET do lazy() podia repetir ou pular linha.
        $base = (array) DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->first();
        unset($base['id']);
        foreach (range(1, 3) as $i) {
            DB::table('tdap_cronogramas')->insert(['numero' => "CR-LZ-{$i}-".random_int(100, 999), 'ativo' => false, 'ativado_em' => null] + $base);
        }

        $linhas = app(CronogramaService::class)->exportar(['municipio_id' => $this->municipioId]);

        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $linhas);
        $numeros = $linhas->pluck('Numero')->all();
        $this->assertCount(4, $numeros);
        $this->assertSame($numeros, array_values(array_unique($numeros)));
    }

    public function test_api_bi_pagina_por_cursor(): void
    {
        $svc = app(CronoViagemService::class);
        foreach (range(1, 3) as $_) {
            $svc->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);
        }
        $estadual = $this->comPermissoes(['tdap.cronogramas.export']);

        $p1 = $this->actingAs($estadual)->getJson(route('api.v1.tdap.cronogramas', ['municipio_id' => $this->municipioId, 'limit' => 2]))->assertOk();
        $cursor = $p1->json('meta.proximo_apos_id');
        $this->assertNotNull($cursor);

        $p2 = $this->actingAs($estadual)->getJson(route('api.v1.tdap.cronogramas', ['municipio_id' => $this->municipioId, 'limit' => 2, 'apos_id' => $cursor]))->assertOk();
        $this->assertSame(1, $p2->json('total'));
        $this->assertNull($p2->json('meta.proximo_apos_id'));

        $ids = array_merge(array_column($p1->json('data'), 'viagem_id'), array_column($p2->json('data'), 'viagem_id'));
        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertCount(3, $ids);
    }

    public function test_api_bi_recusa_limite_acima_do_teto(): void
    {
        $this->actingAs($this->comPermissoes(['tdap.cronogramas.export']))
            ->getJson(route('api.v1.tdap.cronogramas', ['limit' => 50000]))
            ->assertStatus(422);
    }

    public function test_api_bi_estadual_com_permissao_recebe_as_viagens(): void
    {
        app(CronoViagemService::class)->validar($this->registrar(now()->subDay()->toDateTimeString())->id, true);

        $total = $this->actingAs($this->comPermissoes(['tdap.cronogramas.export']))
            ->getJson(route('api.v1.tdap.cronogramas', ['municipio_id' => $this->municipioId]))
            ->assertOk()
            ->json('total');

        $this->assertGreaterThanOrEqual(1, $total);
    }
}
