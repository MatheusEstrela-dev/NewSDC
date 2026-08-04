<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Aviso apos criar uma decretacao.
 *
 * A pagina de criacao ja mostra o toast do canto superior
 * (ProcessoCreate.handleSubmit). O flash de sessao alimenta o
 * FlashNotification, que aparece no canto INFERIOR - as duas mensagens diziam a
 * mesma coisa ao mesmo tempo, entao `store()` deixou de mandar flash.
 *
 * A atualizacao (`update`) continua com flash: aquela tela nao tem toast proprio
 * para todos os caminhos, e remover deixaria a acao sem nenhum retorno visivel.
 */
class ProcessoStoreFlashTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'decretacoes.processos.create',
        'decretacoes.processos.edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function actingAsAnalista(): static
    {
        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSOES);

        return $this->actingAs($user);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'data_entrada' => '2026-03-01',
            'origem'       => 'municipal',
            'municipio_id' => (int) Municipio::query()->value('id'),
            'status'       => 'Aguardando analise',
        ];
    }

    public function test_criacao_redireciona_para_o_wizard_sem_flash_de_sucesso(): void
    {
        $resposta = $this->actingAsAnalista()->post(route('decretacoes.store'), $this->payload());

        $processo = Processo::query()->latest('id')->first();
        $this->assertNotNull($processo);

        $resposta->assertRedirect(route('decretacoes.create', ['id' => $processo->id]));

        // O canto inferior fica em silencio: quem avisa e o toast do topo.
        $resposta->assertSessionMissing('success');
        $resposta->assertSessionHasNoErrors();
    }

    public function test_atualizacao_continua_avisando_por_flash(): void
    {
        $analista = $this->actingAsAnalista();
        $analista->post(route('decretacoes.store'), $this->payload());

        $processo = Processo::query()->latest('id')->first();

        $analista->put(route('decretacoes.update', $processo->id), $this->payload())
            ->assertSessionHas('success');
    }
}
