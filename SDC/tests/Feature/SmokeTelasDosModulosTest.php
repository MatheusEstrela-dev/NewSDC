<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fumaca das telas dos modulos alterados pela adequacao da camada DTO
 * (#63/#66): cada pagina de listagem/cadastro abre sem erro 500 para o
 * super-admin. Pegou, por exemplo, um metodo de Service removido por engano
 * que quebrava visualizar/editar RAT sem que os testes de escrita vissem.
 */
class SmokeTelasDosModulosTest extends TestCase
{
    use DatabaseTransactions;

    public static function rotas(): array
    {
        $nomes = [
            'acessos.index',
            'ajuda-humanitaria.dashboard',
            'ajuda-humanitaria.beneficiarios.index',
            'ajuda-humanitaria.entradas.index',
            'ajuda-humanitaria.estoque.index',
            'ajuda-humanitaria.liberacoes.index',
            'ajuda-humanitaria.materiais.index',
            'ajuda-humanitaria.movimentos.index',
            'ajuda-humanitaria.parametros.index',
            'ajuda-humanitaria.pedidos.index',
            'ajuda-humanitaria.pedidos.create',
            'ajuda-humanitaria.transferencias.index',
            'cedec.contatos.index',
            'cedec.prefeituras.index',
            'cisternas.beneficiarios.index',
            'cisternas.beneficiarios.create',
            'cisternas.comunidades.index',
            'cisternas.lotes.index',
            'cisternas.notificacoes.index',
            'cisternas.ordens-servico.index',
            'compdec.index',
            'compdec.arvore',
            'compdec.create',
            'decretacoes.index',
            'decretacoes.create',
            'demandas.index',
            'demandas.dashboard',
            'demandas.create',
            'estoque.index',
            'estoque.kits.index',
            'estoque.movimentacoes.index',
            'estoque.produtos.index',
            'inventario.index',
            'inventario.estacoes.index',
            'inventario.movimentacoes.index',
            'inventario.remanejamentos.create',
            'plancon.index',
            'plancon.municipios.com',
            'plancon.municipios.sem',
            'plancon.stats',
            'pmda.analises.index',
            'pmda.planos.index',
            'pmda.planos.create',
            'rat.index',
            'rat.arquivados.index',
            'rat.bo.index',
            'rat.create',
            'rat.statistics',
            'suporte.index',
            'tdap.dashboard',
            'tdap.acompanhamento.index',
            'tdap.atas.index',
            'tdap.atas.create',
            'tdap.cronogramas.index',
            'tdap.cronogramas.create',
            'tdap.frota.index',
            'tdap.frota.create',
            'tdap.frota.vistorias.index',
            'tdap.historicos.index',
            'tdap.lotes.index',
            'tdap.lotes.create',
            'tdap.prestadores.index',
            'tdap.prestadores.create',
            'tdap.viagens.confirmacao',
            'tdap.viagens.pendentes',
        ];

        return array_combine($nomes, array_map(fn (string $r) => [$r], $nomes));
    }

    #[DataProvider('rotas')]
    public function test_tela_abre_sem_erro(string $rota): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());

        $status = $this->actingAs($user)->get(route($rota))->getStatusCode();

        $this->assertLessThan(500, $status, "{$rota} respondeu {$status}");
    }
}
