<?php

declare(strict_types=1);

namespace Tests\Feature\Rat;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\RatTermoInterdicao;
use App\Modules\Rat\Services\RatTermoInterdicaoService;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Termo de interdicao do RAT (#57): COMPDEC registra no RAT original (-001),
 * agente CREA emite no relacionado (-002+) a partir do termo da origem, e
 * vistoria com interdicao marcada so finaliza com termo emitido.
 */
class RatTermoInterdicaoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function usuario(array $permissoes, ?string $cargo = null): User
    {
        $user = User::factory()->create();
        if ($cargo !== null) {
            $user->assignRole(Role::where('slug', $cargo)->firstOrFail());
        }
        $user->givePermissionTo(Permission::whereIn('name', $permissoes)->where('guard_name', 'web')->get());

        return $user->fresh();
    }

    private function compdec(): User
    {
        return $this->usuario(['rat.protocolos.view', 'rat.protocolos.edit', 'rat.protocolos.finalizar', 'rat.vistoria.interditar'], 'compdec');
    }

    private function agenteCrea(): User
    {
        return $this->usuario(['rat.protocolos.view', 'rat.protocolos.edit', 'rat.vistoria.crea']);
    }

    private function ratComInterdicao(): RatOcorrencia
    {
        $write = app(RatWriteService::class);
        $o = $write->create();
        $write->saveVistoria($o->id, RatVistoriaDTO::fromArray(['encaminhamentos' => ['interdicao_parcial' => true]]));

        return $o;
    }

    private function termoCompleto(): array
    {
        return [
            'tipo' => 'parcial', 'area_interditada' => 'Cozinha e quarto dos fundos',
            'fundamentacao' => 'Trincas em viga de sustentacao', 'medidas' => 'Escoramento', 'prazo_dias' => 30,
        ];
    }

    public function test_compdec_registra_e_emite_termo_no_rat_original(): void
    {
        $user = $this->compdec();
        $o = $this->ratComInterdicao();
        $this->actingAs($user);

        $this->get(route('rat.edit', $o->id))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('termoInterdicao.relacionado', false)
            ->where('termoInterdicao.interdicao_marcada', true)
            ->where('termoInterdicao.pode_editar', true)
            ->where('termoInterdicao.abrir_por_padrao', true)
            ->where('termoInterdicao.termo', null));

        $this->putJson(route('rat.termo-interdicao.salvar', $o->id), ['tipo' => 'parcial'])->assertOk()
            ->assertJsonPath('termo.status', 'rascunho');

        // Emitir exige area e fundamentacao.
        $this->postJson(route('rat.termo-interdicao.emitir', $o->id))->assertStatus(422);

        // Update parcial preserva o tipo ja gravado.
        $this->putJson(route('rat.termo-interdicao.salvar', $o->id), array_diff_key($this->termoCompleto(), ['tipo' => 1]))
            ->assertOk()->assertJsonPath('termo.tipo', 'parcial')->assertJsonPath('termo.prazo_dias', 30);

        $this->postJson(route('rat.termo-interdicao.emitir', $o->id))->assertOk()
            ->assertJsonPath('termo.status', 'emitido')
            ->assertJsonPath('termo.emitido_por', $user->name);

        // Emitido e definitivo.
        $this->putJson(route('rat.termo-interdicao.salvar', $o->id), ['medidas' => 'outra'])->assertStatus(422);
    }

    public function test_sem_permissao_de_interdicao_nao_registra(): void
    {
        $o = $this->ratComInterdicao();
        $this->actingAs($this->usuario(['rat.protocolos.view', 'rat.protocolos.edit']));

        $this->putJson(route('rat.termo-interdicao.salvar', $o->id), $this->termoCompleto())->assertForbidden();
        $this->assertFalse(RatTermoInterdicao::where('ocorrencia_id', $o->id)->exists());
    }

    public function test_interdicao_marcada_so_finaliza_com_termo_emitido(): void
    {
        $o = $this->ratComInterdicao();
        $this->actingAs($this->compdec());

        $this->patchJson(route('rat.finalize', $o->id))->assertStatus(422);
        $this->putJson(route('rat.termo-interdicao.salvar', $o->id), $this->termoCompleto())->assertOk();
        $this->patchJson(route('rat.finalize', $o->id))->assertStatus(422); // rascunho nao basta

        $this->postJson(route('rat.termo-interdicao.emitir', $o->id))->assertOk();
        $this->patchJson(route('rat.finalize', $o->id))->assertOk();
        $this->assertSame(1, (int) $o->fresh()->status);

        // RAT finalizado nao aceita mais alteracao no termo.
        $this->putJson(route('rat.termo-interdicao.salvar', $o->id), ['medidas' => 'x'])->assertStatus(422);
    }

    public function test_rat_sem_interdicao_finaliza_sem_termo(): void
    {
        $o = app(RatWriteService::class)->create();
        $this->actingAs($this->compdec());

        $this->patchJson(route('rat.finalize', $o->id))->assertOk();
    }

    public function test_criar_ja_finalizado_com_interdicao_e_recusado(): void
    {
        $this->actingAs($this->compdec());

        $this->expectException(HttpException::class);
        app(RatWriteService::class)->createWithData(RatRelatosDTO::fromArray([
            'vistoria'  => ['encaminhamentos' => ['interdicao_total' => true]],
            'finalize'  => true,
        ]));
    }

    public function test_agente_crea_emite_no_relacionado_a_partir_do_termo_da_origem(): void
    {
        $origem = $this->ratComInterdicao();
        $this->actingAs($this->compdec());
        $this->putJson(route('rat.termo-interdicao.salvar', $origem->id), $this->termoCompleto())->assertOk();
        $this->postJson(route('rat.termo-interdicao.emitir', $origem->id))->assertOk();
        $termoOrigem = RatTermoInterdicao::where('ocorrencia_id', $origem->id)->firstOrFail();

        $relacionado = app(RatWriteService::class)->createRelacionado($origem->id);
        $this->assertStringEndsWith('-002', $relacionado->numero_bos);

        // COMPDEC nao emite no relacionado: e papel do agente CREA.
        $this->putJson(route('rat.termo-interdicao.salvar', $relacionado->id), ['registro_crea' => 'MG-1'])->assertForbidden();

        $this->actingAs($this->agenteCrea());
        $this->get(route('rat.edit', $relacionado->id))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('termoInterdicao.relacionado', true)
            ->where('termoInterdicao.exige_crea', true)
            ->where('termoInterdicao.sugestao.area_interditada', 'Cozinha e quarto dos fundos')
            ->where('termoInterdicao.abrir_por_padrao', true));

        // Sem registro CREA nao emite.
        $this->putJson(route('rat.termo-interdicao.salvar', $relacionado->id), ['medidas' => 'Demolicao parcial'])->assertOk()
            ->assertJsonPath('termo.area_interditada', 'Cozinha e quarto dos fundos')
            ->assertJsonPath('termo.tipo', 'parcial')
            ->assertJsonPath('termo.origem_termo_id', $termoOrigem->id)
            ->assertJsonPath('termo.medidas', 'Demolicao parcial');
        $this->postJson(route('rat.termo-interdicao.emitir', $relacionado->id))->assertStatus(422);

        $this->putJson(route('rat.termo-interdicao.salvar', $relacionado->id), ['registro_crea' => 'MG-123456/D'])->assertOk();
        $this->postJson(route('rat.termo-interdicao.emitir', $relacionado->id))->assertOk()
            ->assertJsonPath('termo.registro_crea', 'MG-123456/D')
            ->assertJsonPath('termo.status', 'emitido');
    }

    public function test_service_aponta_permissao_por_tipo_de_rat(): void
    {
        $service = app(RatTermoInterdicaoService::class);
        $origem = app(RatWriteService::class)->create();
        $relacionado = app(RatWriteService::class)->createRelacionado($origem->id);

        $this->assertSame('rat.vistoria.interditar', $service->permissaoExigida($origem));
        $this->assertSame('rat.vistoria.crea', $service->permissaoExigida($relacionado));
    }
}
