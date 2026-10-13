<?php

declare(strict_types=1);

namespace Tests\Feature\Rat;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\User;
use App\Modules\Rat\DTOs\RatBoDTO;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use App\Modules\Rat\Services\RatWriteService;
use App\Services\Auth\SessionVersionService;
use App\Services\Rat\RatNovoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regressao da camada DTO do RAT (#66, docs/plans/checklist_correcoes.md P0):
 * UUID tratado como inteiro, "false" virando true, impossibilidade de limpar
 * campo e defaults que reescreviam colunas nao enviadas.
 */
class RatDtoPersistenciaTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->user = User::factory()->create();
        // O Spatie busca o cargo por `name`; o codigo estavel do projeto e o slug.
        $this->user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());

        // Mudar o cargo incrementa session_version; sem o carimbo que o login
        // real grava, o CheckUserActive derruba a sessao (401).
        $this->actingAs($this->user)->withSession([
            SessionVersionService::SESSION_KEY => $this->user->fresh()->session_version,
        ]);
    }

    private function service(): RatWriteService
    {
        return app(RatWriteService::class);
    }

    private function ocorrencia(): RatOcorrencia
    {
        return $this->service()->create();
    }

    private function vistoria(RatOcorrencia $o): RatRelatoVistoria
    {
        return RatRelatoVistoria::where('ocorrencia_id', $o->id)->firstOrFail();
    }

    // ── Vistoria ────────────────────────────────────────────────────────────

    public function test_vistoria_aninhada_grava_e_depois_limpa_campo_enviado_vazio(): void
    {
        $o = $this->ocorrencia();

        $this->service()->saveVistoria($o->id, RatVistoriaDTO::fromArray([
            'solicitante' => ['nome' => 'Maria', 'bairro' => 'Centro'],
            'patologias'  => ['rachaduras' => true],
        ]));

        $this->service()->saveVistoria($o->id, RatVistoriaDTO::fromArray([
            'solicitante' => ['nome' => null, 'bairro' => ''],
            'patologias'  => ['rachaduras' => false],
        ]));

        $v = $this->vistoria($o)->fresh();
        $this->assertNull($v->v_solicitante_nome, 'campo enviado null deve ser limpo');
        $this->assertNull($v->v_solicitante_bairro, 'campo enviado vazio deve ser limpo');
        $this->assertFalse((bool) $v->v_patologia_rachaduras, 'false explicito deve ser gravado');
    }

    public function test_vistoria_campo_ausente_preserva_valor_anterior(): void
    {
        $o = $this->ocorrencia();

        $this->service()->saveVistoria($o->id, RatVistoriaDTO::fromArray([
            'v_solicitante_nome' => 'Joao',
            'v_bairro'           => 'Savassi',
        ]));
        $this->service()->saveVistoria($o->id, RatVistoriaDTO::fromArray(['v_bairro' => 'Funcionarios']));

        $v = $this->vistoria($o)->fresh();
        $this->assertSame('Joao', $v->v_solicitante_nome);
        $this->assertSame('Funcionarios', $v->v_bairro);
    }

    public function test_vistoria_string_false_nao_vira_true(): void
    {
        $dto = RatVistoriaDTO::fromArray([
            'v_ha_idosos'              => 'false',
            'v_enc_interdicao_total'   => '0',
            'v_enc_interdicao_parcial' => 'true',
        ]);

        $dados = $dto->toArray();
        $this->assertFalse($dados['v_ha_idosos']);
        $this->assertFalse($dados['v_enc_interdicao_total']);
        $this->assertTrue($dados['v_enc_interdicao_parcial']);
    }

    public function test_vistoria_aceita_id_uuid(): void
    {
        $uuid = (string) Str::uuid();

        $this->assertSame($uuid, RatVistoriaDTO::fromArray(['id' => $uuid])->id);
    }

    public function test_vistoria_texto_numerico_invalido_nao_vira_zero(): void
    {
        $dados = RatVistoriaDTO::fromArray([
            'v_numero_moradores' => 'abc',
            'v_latitude'         => 'n/d',
            'v_longitude'        => '-43,94',
        ])->toArray();

        $this->assertNull($dados['v_numero_moradores']);
        $this->assertNull($dados['v_latitude']);
        $this->assertSame(-43.94, $dados['v_longitude']);
    }

    public function test_api_de_vistoria_so_persiste_campos_validados(): void
    {
        $o = $this->ocorrencia();

        $this->postJson(route('rat.ocorrencias.vistoria.store', $o->id), [
            'v_solicitante_nome'      => 'Ana',
            'v_patologia_eflorescencia' => true,
            'v_orgao_crea'            => true,
            'campo_inventado'         => 'x',
        ])->assertCreated();

        $v = $this->vistoria($o)->fresh();
        $this->assertSame('Ana', $v->v_solicitante_nome);
        $this->assertTrue((bool) $v->v_patologia_eflorescencia);
        $this->assertTrue((bool) $v->v_orgao_crea);
    }

    // ── Envolvidos ──────────────────────────────────────────────────────────

    public function test_api_atualiza_envolvido_pelo_uuid_da_rota(): void
    {
        $o = $this->ocorrencia();

        $criado = $this->postJson(route('rat.ocorrencias.envolvidos.store', $o->id), [
            'p_nome_completo' => 'Carlos',
            'p_turista'       => true,
        ])->assertCreated()->json('data.id');

        $this->assertTrue(Str::isUuid((string) $criado));

        $this->putJson(route('rat.ocorrencias.envolvidos.update', [$o->id, $criado]), [
            'p_nome_completo' => 'Carlos Silva',
            'p_turista'       => false,
        ])->assertSuccessful();

        $e = RatRelatoEnvolvidos::findOrFail($criado);
        $this->assertSame('Carlos Silva', $e->p_nome_completo);
        $this->assertFalse((bool) $e->p_turista);
        $this->assertSame(1, RatRelatoEnvolvidos::where('ocorrencia_id', $o->id)->count(), 'update nao pode duplicar');
    }

    // ── Dados gerais e BO ───────────────────────────────────────────────────

    public function test_dados_gerais_sem_tem_vistoria_preserva_o_valor(): void
    {
        $o = $this->ocorrencia();

        $this->service()->saveDadosGerais($o->id, RatDadosGeraisDTO::fromArray(['tem_vistoria' => true, 'local_bairro' => 'Centro']));
        $this->service()->saveDadosGerais($o->id, RatDadosGeraisDTO::fromArray(['local_bairro' => 'Barreiro']));

        $dg = RatRelatoDadosGerais::where('ocorrencia_id', $o->id)->firstOrFail();
        $this->assertTrue((bool) $dg->tem_vistoria);
        $this->assertSame('Barreiro', $dg->local_bairro);
    }

    public function test_bo_mantem_uuid_de_origem_como_string(): void
    {
        $uuid = '0199a3b2-0000-7000-8000-000000000001';

        $this->assertSame($uuid, RatBoDTO::fromArray(['ocorrencia_origem_id' => $uuid])->ocorrenciaOrigemId);
    }

    // ── Formulario principal (payload aninhado da tela) ─────────────────────

    private function payloadFormulario(): array
    {
        return [
            'dadosGerais' => ['data_fato' => '2026-10-01 08:00:00', 'nat_nome_operacao' => 'Deslizamento', 'tem_vistoria' => true],
            'local'       => ['local_bairro' => 'Centro', 'municipio_nome' => 'Ouro Preto', 'local_latitude' => '-20,38'],
            'envolvidos'  => [['p_nome_completo' => 'Pessoa Um', 'p_turista' => false]],
            'recursos'    => [['recurso_tipo' => 'viatura', 'viatura_placa' => 'ABC1D23', 'recurso_problemas' => false]],
            'vistoria'    => [
                'solicitante'     => ['nome' => 'Solicitante', 'bairro' => 'Centro'],
                'patologias'      => ['rachaduras' => true, 'trincas' => false],
                'encaminhamentos' => ['interdicao_total' => true],
                'orgaos_acionados' => ['crea' => true, 'pm' => true],
            ],
        ];
    }

    public function test_formulario_cria_e_edita_rat_completo(): void
    {
        $id = $this->postJson(route('rat.store'), $this->payloadFormulario())
            ->assertCreated()
            ->json('id');

        $dg = RatRelatoDadosGerais::where('ocorrencia_id', $id)->firstOrFail();
        $this->assertSame('Centro', $dg->local_bairro);
        $this->assertSame('Ouro Preto', $dg->local_municipio_nome);
        $this->assertEqualsWithDelta(-20.38, (float) $dg->local_latitude, 0.0001);
        $this->assertTrue((bool) $dg->tem_vistoria);

        $v = RatRelatoVistoria::where('ocorrencia_id', $id)->firstOrFail();
        $this->assertTrue((bool) $v->v_patologia_rachaduras);
        $this->assertFalse((bool) $v->v_patologia_trincas);
        $this->assertTrue((bool) $v->v_enc_interdicao_total);
        $this->assertSame('sim', $v->v_orgao_pm);
        $this->assertSame(1, RatRelatoEnvolvidos::where('ocorrencia_id', $id)->count());

        // Edicao pela tela: limpa o bairro, desmarca a interdicao, troca o envolvido.
        $edicao = $this->payloadFormulario();
        $edicao['local']['local_bairro'] = '';
        $edicao['vistoria']['encaminhamentos']['interdicao_total'] = false;
        $edicao['vistoria']['orgaos_acionados']['pm'] = false;
        $edicao['envolvidos'] = [['p_nome_completo' => 'Pessoa Dois']];

        $this->putJson(route('rat.update', $id), $edicao)->assertOk();

        $dg->refresh();
        $v->refresh();
        $this->assertNull($dg->local_bairro, 'campo limpo na tela deve ser limpo no banco');
        $this->assertTrue((bool) $dg->tem_vistoria);
        $this->assertFalse((bool) $v->v_enc_interdicao_total);
        $this->assertNull($v->v_orgao_pm);
        $this->assertTrue((bool) $v->v_patologia_rachaduras, 'o que nao mudou permanece');
        $this->assertSame(
            ['Pessoa Dois'],
            RatRelatoEnvolvidos::where('ocorrencia_id', $id)->pluck('p_nome_completo')->all(),
        );
    }

    public function test_rascunho_preserva_o_que_nao_foi_enviado(): void
    {
        $id = $this->postJson(route('rat.store'), $this->payloadFormulario())->assertCreated()->json('id');

        $this->postJson(route('rat.draft', $id), ['local' => ['local_bairro' => 'Pilar']])->assertOk();

        $dg = RatRelatoDadosGerais::where('ocorrencia_id', $id)->firstOrFail();
        $this->assertSame('Pilar', $dg->local_bairro);
        $this->assertSame('Ouro Preto', $dg->local_municipio_nome);
        $this->assertTrue((bool) $dg->tem_vistoria);
        $this->assertTrue((bool) RatRelatoVistoria::where('ocorrencia_id', $id)->value('v_patologia_rachaduras'));
    }

    // ── API v1 e rat-novo (camada de service) ───────────────────────────────

    public function test_update_da_api_mescla_envolvidos_por_id_sem_duplicar(): void
    {
        $o = $this->ocorrencia();
        $e = $this->service()->saveEnvolvido($o->id, \App\Modules\Rat\DTOs\RatEnvolvidoDTO::fromArray(['p_nome_completo' => 'Antes']));

        $this->service()->atualizar($o->id, RatRelatosDTO::fromArray([
            'envolvidos' => [
                ['id' => $e->id, 'p_nome_completo' => 'Depois'],
                ['p_nome_completo' => 'Novo'],
            ],
        ]), substituirListas: false);

        $nomes = RatRelatoEnvolvidos::where('ocorrencia_id', $o->id)->orderBy('p_nome_completo')->pluck('p_nome_completo')->all();
        $this->assertSame(['Depois', 'Novo'], $nomes);
    }

    public function test_formulario_substitui_a_lista_de_envolvidos(): void
    {
        $o = $this->ocorrencia();
        $e = $this->service()->saveEnvolvido($o->id, \App\Modules\Rat\DTOs\RatEnvolvidoDTO::fromArray(['p_nome_completo' => 'Antigo']));

        $this->service()->atualizar($o->id, RatRelatosDTO::fromArray([
            'envolvidos' => [['id' => $e->id, 'p_nome_completo' => 'Unico']],
        ]));

        $this->assertSame(['Unico'], RatRelatoEnvolvidos::where('ocorrencia_id', $o->id)->pluck('p_nome_completo')->all());
    }

    public function test_rat_novo_grava_dados_gerais_e_aceita_uuid(): void
    {
        $novo = app(RatNovoService::class);

        $o = $novo->createRat(RatRelatosDTO::fromArray(['dadosGerais' => ['nat_nome_operacao' => 'Enchente']]));
        $this->assertSame('Enchente', RatRelatoDadosGerais::where('ocorrencia_id', $o->id)->value('nat_nome_operacao'));

        $this->assertSame('RAT-TESTE-UUID', $novo->updateRat($o->id, ['numero_bos' => 'RAT-TESTE-UUID'])->numero_bos);

        $novo->deleteRat($o->id);
        $this->assertNull(RatOcorrencia::find($o->id));
    }

    // ── Historico (API v1) ──────────────────────────────────────────────────

    public function test_historico_da_ocorrencia_aceita_uuid(): void
    {
        $o = $this->ocorrencia();

        $this->getJson(route('api.v1.rat.historico.index', $o->id))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total', 'current_page']]);
    }
}
