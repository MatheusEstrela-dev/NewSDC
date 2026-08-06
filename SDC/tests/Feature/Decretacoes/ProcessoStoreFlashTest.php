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
 * Aviso apos criar/atualizar uma decretacao.
 *
 * As telas de criacao e edicao mostram o proprio toast
 * (ProcessoCreate.handleSubmit e ProcessoEditTemplate), e o flash de sessao
 * alimenta o FlashNotification global - as duas mensagens diziam a mesma coisa
 * ao mesmo tempo. Por isso `store()`, `update()` e `storeDesastres()` deixaram
 * de mandar flash de SUCESSO; o de ERRO continua, pois nao tem equivalente na
 * tela.
 *
 * Nota: o FlashNotification foi movido para o canto SUPERIOR (mesma ancora do
 * ToastContainer). Fosse mantido o flash de sucesso, as duas notificacoes
 * apareceriam sobrepostas no mesmo canto.
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

    public function test_atualizacao_nao_manda_flash_de_sucesso(): void
    {
        $analista = $this->actingAsAnalista();
        $analista->post(route('decretacoes.store'), $this->payload());

        $processo = Processo::query()->latest('id')->first();

        // Quem avisa e o toast da propria tela ("Identificacao atualizada." no
        // wizard, "Processo atualizado com sucesso." na edicao).
        $analista->put(route('decretacoes.update', $processo->id), $this->payload())
            ->assertSessionMissing('success')
            ->assertSessionHasNoErrors();
    }
}
