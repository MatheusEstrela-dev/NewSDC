<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Models\User;
use App\Modules\Decretacoes\Filters\ProcessoFilter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Props que a listagem entrega ao Vue.
 *
 * Cobre o que o modal "Exportar por REDEC" consome (`filterOptions.redecs`) e o
 * eco dos filtros aplicados (`filters.redec_id`), que e o valor inicial do modal
 * e o que mantem o select de REDEC preenchido depois do reload.
 */
class ProcessoIndexPropsTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'decretacoes.processos.view',
        'decretacoes.processos.export',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        ProcessoFilter::clearCache();
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

    public function test_filtro_de_redec_volta_nas_props(): void
    {
        $this->actingAsAnalista()
            ->get(route('decretacoes.index', ['redec_id' => 7]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Decretacoes/ProcessoIndex')
                ->where('filters.redec_id', '7')
            );
    }

    /**
     * `filterOptions.redecs` e a prop que alimenta o select do modal de
     * exportacao (e o filtro de REDEC da listagem). Chega ao Vue via
     * Inertia::lazy(), no reload parcial que ProcessoIndex.vue dispara no
     * onMounted; aqui o que se fixa e o CONTRATO de cada opcao.
     */
    public function test_opcoes_de_redec_seguem_o_contrato_do_select(): void
    {
        $redecs = ProcessoFilter::getFilterOptions()['redecs'];

        $this->assertIsArray($redecs);
        $this->assertCount(14, $redecs, 'Minas Gerais tem 14 REDECs.');

        // O select le `id` (SelectInput usa option.value ?? option.id) e `label`;
        // `sigla` rotula o campo de municipio quando ha REDEC escolhida.
        $this->assertSame(['id', 'label', 'sigla'], array_keys($redecs[0]));
        $this->assertSame(1, $redecs[0]['id']);
        $this->assertSame('1ª REDEC', $redecs[0]['sigla']);
        $this->assertSame('1ª REDEC - Metropolitana de Belo Horizonte', $redecs[0]['label']);
    }
}
