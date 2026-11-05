<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Enums\FaseCronograma;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Services\TramitacaoCronogramaService;
use App\Services\Auth\SessionVersionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Acompanhamento de cronograma: as 4 fases da tramitacao, quem pode mover
 * cada uma e a trava da ativacao direta.
 */
class TdapAcompanhamentoCronogramaTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private int $municipioId;
    private int $cronogramaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        // Os testes de fluxo usam um usuario so; a segregacao tem testes proprios.
        config(['tdap.cronogramas.exige_tramite' => true, 'tdap.cronogramas.segregacao_funcoes' => false]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->admin = $this->admin->fresh();
        $this->actingAs($this->admin);

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $this->municipioId = Municipio::factory()->create()->id;
        $ata = DB::table('tdap_atas')->insertGetId(['numero' => 'AC-' . random_int(1000, 9999), 'dt_inicio' => now()->subMonth()->toDateString(), 'dt_final' => now()->addYear()->toDateString()] + $agora);
        $prestador = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador AC', 'email' => 'ac@t.local'] + $agora);
        $lote = DB::table('tdap_lotes')->insertGetId(['ata_id' => $ata, 'prestador_id' => $prestador, 'numero' => 'L-AC', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        $caminhao = DB::table('tdap_caminhoes')->insertGetId(['prestador_id' => $prestador, 'placa' => 'ACM' . random_int(1, 9) . 'A' . random_int(10, 99), 'capacidade_m3' => 8, 'ativo' => true] + $agora);
        $this->cronogramaId = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-AC-' . random_int(100, 999), 'ata_id' => $ata, 'lote_id' => $lote, 'municipio_id' => $this->municipioId,
            'prestador_id' => $prestador, 'dt_inicio' => now()->toDateString(), 'dt_final' => now()->addMonth()->toDateString(),
            'ativo' => false,
        ] + $agora);
        DB::table('tdap_crono_caminhoes')->insertGetId(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $caminhao] + $agora);
    }

    private function servico(): TramitacaoCronogramaService
    {
        return app(TramitacaoCronogramaService::class);
    }

    private function fase(): FaseCronograma
    {
        return Cronograma::findOrFail($this->cronogramaId)->fase_tramite;
    }

    private function usuarioDoPapel(string $papel, ?int $municipioId = null): User
    {
        $dados = [];
        if ($municipioId !== null) {
            $dados['orgao_principal_id'] = Orgao::factory()->compdec()->create(['municipio_id' => $municipioId])->id;
        }
        $user = User::factory()->create($dados);
        $user->assignRole(Role::where('slug', $papel)->firstOrFail());

        return $user->fresh();
    }

    public function test_cronograma_novo_comeca_em_elaboracao(): void
    {
        $this->assertSame(FaseCronograma::Elaboracao, $this->fase());
    }

    public function test_enviar_e_aprovar_movem_a_fase_e_deixam_trilha(): void
    {
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);
        $this->assertSame(FaseCronograma::EmAnalise, $this->fase());

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Aprovar, $this->admin, 'Ok técnico');
        $this->assertSame(FaseCronograma::Aprovado, $this->fase());

        $this->assertDatabaseHas('tdap_cronograma_tramites', [
            'cronograma_id' => $this->cronogramaId, 'acao' => 'aprovar',
            'fase_anterior' => 'em_analise', 'fase_nova' => 'aprovado', 'observacao' => 'Ok técnico',
        ]);
        $this->assertDatabaseHas('tdap_historicos', [
            'entity_type' => 'cronograma', 'entity_id' => $this->cronogramaId, 'tipo_evento' => 'cronograma.aprovado',
        ]);
    }

    public function test_devolver_volta_uma_fase(): void
    {
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Devolver, $this->admin, 'Falta empenho');

        $this->assertSame(FaseCronograma::Elaboracao, $this->fase());
    }

    public function test_devolver_sem_motivo_e_recusado_na_rota(): void
    {
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);

        $this->post(route('tdap.acompanhamento.tramitar', $this->cronogramaId), ['acao' => 'devolver'])
            ->assertSessionHasErrors('observacao');

        $this->assertSame(FaseCronograma::EmAnalise, $this->fase());
    }

    public function test_acao_fora_da_fase_e_bloqueada(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('não se aplica');

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Aprovar, $this->admin);
    }

    public function test_quem_nao_analisa_nao_aprova(): void
    {
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);
        $visualizador = $this->usuarioDoPapel('viewer');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente o responsável');

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Aprovar, $visualizador);
    }

    public function test_enviar_exige_caminhao_alocado(): void
    {
        DB::table('tdap_crono_caminhoes')->where('cronograma_id', $this->cronogramaId)->delete();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('caminhão');

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);
    }

    public function test_ativacao_direta_exige_fase_aprovado(): void
    {
        [$ok, $motivo] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertFalse($ok);
        $this->assertStringContainsString('aprovado na analise', $motivo);
    }

    public function test_com_a_chave_desligada_a_ativacao_ignora_a_fase(): void
    {
        config(['tdap.cronogramas.exige_tramite' => false]);

        [, $motivo] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        // Passa da guarda da fase e para na proxima (vistoria do caminhao).
        $this->assertStringNotContainsString('aprovado na analise', (string) $motivo);
    }

    public function test_aprovado_passa_da_guarda_da_fase_ao_ativar(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['fase_tramite' => 'aprovado']);

        try {
            $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Ativar, $this->admin);
            $this->assertSame(FaseCronograma::EmExecucao, $this->fase());
        } catch (\DomainException $e) {
            // Sem vistoria aprovada no fixture: o bloqueio tem de ser o da
            // vistoria, nunca o da fase.
            $this->assertStringContainsString('vistoria', $e->getMessage());
        }
    }

    public function test_painel_agrupa_pelas_quatro_fases(): void
    {
        $this->get(route('tdap.acompanhamento.index', ['municipio_id' => $this->municipioId]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Tdap/Acompanhamento/Index')
                ->has('colunas', 5)
                ->where('colunas.0.value', 'elaboracao')
                ->where('colunas.0.total', 1)
                ->where('colunas.0.cards.0.id', $this->cronogramaId));
    }

    public function test_painel_exibe_apenas_os_aprovados(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['fase_tramite' => 'aprovado']);

        $this->get(route('tdap.acompanhamento.index', ['fase' => 'aprovado', 'municipio_id' => $this->municipioId]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('colunas', 1)
                ->where('colunas.0.value', 'aprovado')
                ->where('colunas.0.cards.0.id', $this->cronogramaId));
    }

    public function test_encerrar_leva_o_cronograma_para_entregues(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)
            ->update(['ativo' => true, 'ativado_em' => now(), 'fase_tramite' => 'em_execucao']);

        app(CronogramaService::class)->encerrar($this->cronogramaId, 'Água entregue');

        $this->assertSame(FaseCronograma::Entregue, $this->fase());
        $this->assertDatabaseHas('tdap_cronograma_tramites', [
            'cronograma_id' => $this->cronogramaId, 'acao' => 'encerrar', 'fase_nova' => 'entregue',
        ]);

        $this->get(route('tdap.acompanhamento.index', ['fase' => 'entregue', 'municipio_id' => $this->municipioId]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('colunas.0.label', 'Entregues')
                ->where('colunas.0.cards.0.id', $this->cronogramaId));
    }

    public function test_painel_respeita_o_municipio_de_quem_consulta(): void
    {
        $outroMunicipio = Municipio::factory()->create()->id;
        $compdec = $this->usuarioDoPapel('compdec', $outroMunicipio);
        $compdec->givePermissionTo('tdap.acompanhamento.view');
        $compdec = $compdec->fresh();

        // givePermissionTo sobe users.session_version; sem gravar a versao na
        // sessao o CheckUserActive derruba o request.
        $this->actingAs($compdec)
            ->withSession([SessionVersionService::SESSION_KEY => $compdec->session_version])
            ->get(route('tdap.acompanhamento.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('colunas.0.cards', fn ($cards) => collect($cards)->doesntContain('id', $this->cronogramaId)));

        $this->get(route('tdap.acompanhamento.detalhe', $this->cronogramaId))->assertForbidden();
    }

    public function test_listagem_filtra_e_mostra_pela_fase(): void
    {
        $ids = fn (string $estado) => collect(
            $this->get(route('tdap.cronogramas.index', ['estado' => $estado, 'municipio_id' => $this->municipioId]))
                ->assertOk()->viewData('page')['props']['cronogramas']['data'],
        );

        $naElaboracao = $ids('elaboracao');
        $this->assertTrue($naElaboracao->contains('id', $this->cronogramaId));
        $this->assertSame('elaboracao', $naElaboracao->firstWhere('id', $this->cronogramaId)['fase_tramite']['value']);
        $this->assertFalse($ids('em_analise')->contains('id', $this->cronogramaId));
        $this->assertFalse($ids('em_execucao')->contains('id', $this->cronogramaId));
    }

    public function test_entregue_sem_tramite_mostra_o_fluxo_do_cronograma(): void
    {
        // Acervo anterior ao Acompanhamento: encerrado, sem tramite gravado.
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update([
            'ativo' => false, 'created_at' => now()->subMonths(2), 'ativado_em' => now()->subMonth(),
            'encerrado_em' => now()->subDay(), 'fase_tramite' => 'entregue',
        ]);

        $resposta = $this->getJson(route('tdap.acompanhamento.detalhe', $this->cronogramaId))
            ->assertOk()
            ->assertJsonPath('fase.value', 'entregue')
            ->assertJsonPath('resumo.viagens_realizadas', 0);

        $acoes = collect($resposta->json('tramites'))->pluck('acao');
        $this->assertEqualsCanonicalizing(['encerrar', 'ativar', 'criacao'], $acoes->all());
        $this->assertSame('encerrar', $acoes->first(), 'linha do tempo do mais recente para o mais antigo');
    }

    public function test_filtros_completos_do_painel(): void
    {
        $ids = function (array $filtros) {
            $colunas = $this->get(route('tdap.acompanhamento.index', $filtros + ['municipio_id' => $this->municipioId]))
                ->assertOk()->viewData('page')['props']['colunas'];

            return collect($colunas)->flatMap(fn ($c) => collect($c['cards'])->pluck('id'));
        };

        // Busca pelo nome do prestador, sem acento e sem caixa.
        DB::table('tdap_prestadores')->where('nome', 'Prestador AC')->update(['nome' => 'Águas Teste AC']);
        $this->assertTrue($ids(['search' => 'aguas teste'])->contains($this->cronogramaId));
        $this->assertFalse($ids(['search' => 'nao-existe-xyz'])->contains($this->cronogramaId));

        $this->assertTrue($ids(['caminhoes' => 'com'])->contains($this->cronogramaId));
        $this->assertFalse($ids(['caminhoes' => 'sem'])->contains($this->cronogramaId));

        // Vigencia de hoje a +1 mes: nao cruza o ano que vem, cruza amanha.
        $this->assertFalse($ids(['vigencia_de' => now()->addYear()->toDateString()])->contains($this->cronogramaId));
        $this->assertTrue($ids(['vigencia_ate' => now()->addDay()->toDateString()])->contains($this->cronogramaId));

        // Criado agora: nao esta parado ha mais de 7 dias.
        $this->assertFalse($ids(['parado_ha' => 7])->contains($this->cronogramaId));
    }

    public function test_cronograma_em_analise_ou_aprovado_nao_aceita_edicao(): void
    {
        $caminhao = DB::table('tdap_crono_caminhoes')->where('cronograma_id', $this->cronogramaId)->first();
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);

        $this->assertTrue(Cronograma::findOrFail($this->cronogramaId)->tramitacao_travada);

        try {
            app(\App\Modules\Tdap\Services\CronoCaminhaoService::class)->remover($caminhao->id);
            $this->fail('remover deveria ser recusado em analise');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Devolva-o', $e->getMessage());
        }

        // Devolvido, volta a ser editavel.
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Devolver, $this->admin, 'Ajustar caminhoes');
        $this->assertFalse(Cronograma::findOrFail($this->cronogramaId)->tramitacao_travada);
        $this->assertTrue(app(\App\Modules\Tdap\Services\CronoCaminhaoService::class)->remover($caminhao->id));
    }

    public function test_cronograma_arquivado_nao_pode_ser_ativado_pela_ficha(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)
            ->update(['fase_tramite' => 'aprovado', 'arquivado_em' => now()]);

        [$ok, $motivo] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertFalse($ok);
        $this->assertStringContainsString('arquivado', $motivo);
    }

    public function test_encerrar_duas_vezes_gera_um_tramite_so(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)
            ->update(['ativo' => true, 'ativado_em' => now(), 'fase_tramite' => 'em_execucao']);

        app(CronogramaService::class)->encerrar($this->cronogramaId);

        try {
            app(CronogramaService::class)->encerrar($this->cronogramaId);
        } catch (\DomainException) {
        }

        $this->assertSame(1, DB::table('tdap_cronograma_tramites')
            ->where('cronograma_id', $this->cronogramaId)->where('acao', 'encerrar')->count());
    }

    public function test_parametros_invalidos_do_painel_nao_derrubam_a_tela(): void
    {
        $this->get(route('tdap.acompanhamento.index', [
            'vigencia_de' => 'abc', 'search' => ['x'], 'parado_ha' => 'muito', 'fase' => 'inexistente', 'caminhoes' => 'talvez',
        ]))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('colunas', 5)
            ->where('filtros', []));
    }

    public function test_coluna_traz_total_real_e_limita_os_cards(): void
    {
        $this->assertSame(200, (new \ReflectionClassConstant(TramitacaoCronogramaService::class, 'LIMITE_POR_FASE'))->getValue());

        $colunas = $this->servico()->painel(['municipio_id' => $this->municipioId], $this->admin, null);

        $this->assertSame(1, $colunas[0]['total']);
        $this->assertCount(1, $colunas[0]['cards']);
    }

    public function test_tramitar_pela_rota_recusa_outro_municipio_e_mostra_erro_de_negocio(): void
    {
        $compdec = $this->usuarioDoPapel('compdec', Municipio::factory()->create()->id);
        $compdec->givePermissionTo('tdap.cronogramas.enviar');
        $compdec = $compdec->fresh();

        $this->actingAs($compdec)
            ->withSession([SessionVersionService::SESSION_KEY => $compdec->session_version])
            ->post(route('tdap.acompanhamento.tramitar', $this->cronogramaId), ['acao' => 'enviar'])
            ->assertForbidden();

        // Acao que nao se aplica a fase: volta com flash de erro, sem mudar nada.
        $this->actingAs($this->admin)
            ->post(route('tdap.acompanhamento.tramitar', $this->cronogramaId), ['acao' => 'aprovar'])
            ->assertSessionHas('error');
        $this->assertSame(FaseCronograma::Elaboracao, $this->fase());
    }

    public function test_cada_acao_exige_a_permissao_do_seu_profissional(): void
    {
        $analista = $this->usuarioDoPapel('analyst'); // cadastra e envia
        $gestor = $this->usuarioDoPapel('manager');   // analisa e ativa

        $this->assertTrue($analista->can('tdap.cronogramas.enviar'));
        $this->assertFalse($analista->can('tdap.cronogramas.analisar'));

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $analista);
        $this->assertSame(FaseCronograma::EmAnalise, $this->fase());

        // O analista nao ve nem consegue usar os botoes da analise.
        $this->assertSame([], $this->servico()->acoesDisponiveis(Cronograma::findOrFail($this->cronogramaId), $analista));
        try {
            $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Aprovar, $analista);
            $this->fail('analista sem `analisar` nao pode aprovar');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Somente o responsável', $e->getMessage());
        }

        $acoesDoGestor = collect($this->servico()->acoesDisponiveis(Cronograma::findOrFail($this->cronogramaId), $gestor))->pluck('value');
        $this->assertEqualsCanonicalizing(['aprovar', 'devolver'], $acoesDoGestor->all());
    }

    public function test_segregacao_quem_envia_nao_aprova_e_quem_aprova_nao_ativa(): void
    {
        config(['tdap.cronogramas.segregacao_funcoes' => true]);
        $outroGestor = $this->usuarioDoPapel('manager');

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Enviar, $this->admin);

        try {
            $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Aprovar, $this->admin);
            $this->fail('quem enviou nao pode aprovar');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Quem enviou', $e->getMessage());
        }

        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Aprovar, $outroGestor);
        $this->assertSame(FaseCronograma::Aprovado, $this->fase());

        // O bloqueio aparece no botao de quem aprovou, com o motivo.
        $ativar = collect($this->servico()->acoesDisponiveis(Cronograma::findOrFail($this->cronogramaId), $outroGestor))
            ->firstWhere('value', 'ativar');
        $this->assertStringContainsString('Quem aprovou', (string) $ativar['bloqueio']);

        // Quem enviou (e nao aprovou) pode devolver a analise.
        $this->servico()->tramitar($this->cronogramaId, AcaoTramite::Devolver, $this->admin, 'Rever volumes');
        $this->assertSame(FaseCronograma::EmAnalise, $this->fase());
    }

    public function test_encerrar_pela_ficha_exige_permissao_propria(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['ativo' => true, 'ativado_em' => now()]);
        $visualizador = $this->usuarioDoPapel('viewer');

        $this->actingAs($visualizador)
            ->post(route('tdap.cronogramas.encerrar', $this->cronogramaId))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('tdap.cronogramas.encerrar', $this->cronogramaId))
            ->assertRedirect();
        $this->assertSame(FaseCronograma::Entregue, $this->fase());
    }

    public function test_fase_acompanha_o_estado_mesmo_em_escrita_direta(): void
    {
        // Ativo gravado direto (ETL/importacao): vira em_execucao sozinho.
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['ativo' => true]);
        $this->assertSame(FaseCronograma::EmExecucao, $this->fase());

        // Tentar por a fase de rascunho num ativo nao pega.
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['fase_tramite' => 'aprovado']);
        $this->assertSame(FaseCronograma::EmExecucao, $this->fase());

        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['ativo' => false, 'encerrado_em' => now()]);
        $this->assertSame(FaseCronograma::Entregue, $this->fase());
    }

    public function test_fase_nao_entra_por_atribuicao_em_massa(): void
    {
        $cronograma = Cronograma::findOrFail($this->cronogramaId);
        $cronograma->fill(['fase_tramite' => 'aprovado', 'observacao' => 'x']);

        $this->assertSame(FaseCronograma::Elaboracao, $cronograma->fase_tramite);
        $this->assertSame('x', $cronograma->observacao);
    }

    public function test_detalhe_de_outro_municipio_e_proibido(): void
    {
        $compdec = $this->usuarioDoPapel('compdec', Municipio::factory()->create()->id);

        $this->actingAs($compdec)
            ->getJson(route('tdap.acompanhamento.detalhe', $this->cronogramaId))
            ->assertForbidden();
    }

    public function test_detalhe_traz_acoes_do_responsavel(): void
    {
        $this->getJson(route('tdap.acompanhamento.detalhe', $this->cronogramaId))
            ->assertOk()
            ->assertJsonPath('fase.value', 'elaboracao')
            ->assertJsonPath('acoes.0.value', 'enviar')
            ->assertJsonPath('acoes.0.bloqueio', null)
            ->assertJsonCount(5, 'fases');
    }
}
