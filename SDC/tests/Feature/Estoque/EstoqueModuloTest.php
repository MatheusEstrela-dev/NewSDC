<?php

declare(strict_types=1);

namespace Tests\Feature\Estoque;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Estoque\DTOs\EntradaEstoqueDTO;
use App\Modules\Estoque\DTOs\SaidaEstoqueDTO;
use App\Modules\Estoque\Models\Almoxarifado;
use App\Modules\Estoque\Models\Kit;
use App\Modules\Estoque\Models\Lote;
use App\Modules\Estoque\Models\MovimentacaoEstoque;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Services\EstoqueConsultaService;
use App\Modules\Estoque\Services\MovimentacaoEstoqueService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Modulo /estoque (#32 inventario de donativos, #33 estoque e kits): entrada
 * por lote, saida FEFO com romaneio, alertas, kits, tela e API v1.
 */
class EstoqueModuloTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Almoxarifado $almox;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->user = $this->user->fresh();
        $this->actingAs($this->user);

        $this->almox = Almoxarifado::create(['nome' => 'Deposito Teste', 'tipo' => 'regional', 'capacidade' => 1000, 'ativo' => true]);
    }

    private function produto(array $extra = []): Produto
    {
        return Produto::create($extra + [
            'sku' => 'T-' . strtoupper(uniqid()), 'nome' => 'Cesta basica teste', 'categoria' => 'alimentos',
            'unidade_base' => 'un', 'estoque_minimo' => 0, 'controla_validade' => true,
        ]);
    }

    private function entrada(Produto $p, string $lote, ?string $validade, int $qtd): MovimentacaoEstoque
    {
        return app(MovimentacaoEstoqueService::class)->entrada(EntradaEstoqueDTO::fromValidated([
            'produto_id' => $p->id, 'almoxarifado_id' => $this->almox->id, 'lote' => $lote,
            'validade' => $validade, 'quantidade' => $qtd, 'fornecedor' => 'Doador X',
        ]), $this->user->id);
    }

    private function saida(array $itens): array
    {
        return app(MovimentacaoEstoqueService::class)->saida(SaidaEstoqueDTO::fromValidated([
            'almoxarifado_id' => $this->almox->id, 'destino_tipo' => 'abrigo', 'destino_descricao' => 'Abrigo Escola',
            'itens' => $itens,
        ]), $this->user->id);
    }

    // ── Entrada ────────────────────────────────────────────────────────────

    public function test_entrada_em_lote_soma_no_mesmo_lote_e_gera_extrato(): void
    {
        $p = $this->produto();
        $this->entrada($p, 'l-1', '2027-01-31', 40);
        $mov = $this->entrada($p, 'L-1', '2027-01-31', 10);

        $lote = Lote::where('produto_id', $p->id)->sole();
        $this->assertSame('L-1', $lote->codigo);
        $this->assertSame(50, $lote->saldo);
        $this->assertSame(50, $mov->saldo_apos);
        $this->assertSame('Doador X', $lote->fornecedor);
        $this->assertSame(2, MovimentacaoEstoque::where('lote_id', $lote->id)->count());
    }

    public function test_entrada_recusa_lote_com_outra_validade_e_produto_sem_validade(): void
    {
        $p = $this->produto();
        $this->entrada($p, 'L-2', '2027-01-31', 5);

        try {
            $this->entrada($p, 'L-2', '2027-06-30', 5);
            $this->fail('validade divergente deveria ser recusada');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('31/01/2027', $e->getMessage());
        }

        $this->expectExceptionMessage('controla validade');
        $this->entrada($p, 'L-3', null, 5);
    }

    // ── Saida ──────────────────────────────────────────────────────────────

    public function test_saida_fefo_pula_vencido_e_quarentena_e_gera_romaneio(): void
    {
        $p = $this->produto();
        $this->entrada($p, 'VENCE-DEPOIS', now()->addMonths(6)->toDateString(), 30);
        $this->entrada($p, 'VENCE-ANTES', now()->addMonths(1)->toDateString(), 10);
        $vencido = Lote::create(['produto_id' => $p->id, 'almoxarifado_id' => $this->almox->id, 'codigo' => 'VENCIDO', 'validade' => now()->subDay(), 'saldo' => 100]);
        $retido = Lote::create(['produto_id' => $p->id, 'almoxarifado_id' => $this->almox->id, 'codigo' => 'RETIDO', 'validade' => now()->addDays(2), 'saldo' => 100, 'bloqueado' => true]);

        $r1 = $this->saida([['produto_id' => $p->id, 'quantidade' => 15]]);

        $this->assertMatchesRegularExpression('/^ROM-\d{4}-\d{5}$/', $r1['romaneio']);
        $this->assertSame(0, Lote::where('codigo', 'VENCE-ANTES')->value('saldo'), 'vence primeiro, sai primeiro');
        $this->assertSame(25, Lote::where('codigo', 'VENCE-DEPOIS')->value('saldo'));
        $this->assertSame(100, $vencido->fresh()->saldo);
        $this->assertSame(100, $retido->fresh()->saldo);
        $this->assertCount(2, $r1['movimentacoes']);

        $r2 = $this->saida([['produto_id' => $p->id, 'quantidade' => 1]]);
        $this->assertSame((int) substr($r1['romaneio'], -5) + 1, (int) substr($r2['romaneio'], -5));
    }

    public function test_saida_sem_saldo_recusa_o_romaneio_inteiro(): void
    {
        $a = $this->produto(['nome' => 'Colchao']);
        $b = $this->produto(['nome' => 'Kit higiene']);
        $this->entrada($a, 'A1', '2030-01-01', 10);
        $this->entrada($b, 'B1', '2030-01-01', 2);

        try {
            // Mesmo produto em duas linhas soma: 3 + 3 > 2.
            $this->saida([['produto_id' => $a->id, 'quantidade' => 5], ['produto_id' => $b->id, 'quantidade' => 3], ['produto_id' => $b->id, 'quantidade' => 3]]);
            $this->fail('saldo insuficiente deveria recusar');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Kit higiene: pedido 6, disponivel 2', $e->getMessage());
        }

        $this->assertSame(10, Lote::where('codigo', 'A1')->value('saldo'), 'nada saiu');
        $this->assertSame(0, MovimentacaoEstoque::where('tipo', 'saida')->where('produto_id', $a->id)->count());
    }

    // ── Painel ─────────────────────────────────────────────────────────────

    public function test_painel_alertas_ocupacao_e_kits_montaveis(): void
    {
        $sabonete = $this->produto(['nome' => 'Sabonete', 'categoria' => 'higiene', 'estoque_minimo' => 100]);
        $escova = $this->produto(['nome' => 'Escova', 'categoria' => 'higiene']);
        $this->entrada($sabonete, 'S1', now()->addDays(10)->toDateString(), 20);
        $this->entrada($escova, 'E1', '2030-01-01', 7);

        $kit = Kit::create(['codigo' => 'KIT-H', 'nome' => 'Kit higiene']);
        $kit->itens()->create(['produto_id' => $sabonete->id, 'quantidade' => 2]);
        $kit->itens()->create(['produto_id' => $escova->id, 'quantidade' => 1]);

        $consulta = app(EstoqueConsultaService::class);
        $kitInfo = collect($consulta->kits())->firstWhere('codigo', 'KIT-H');
        $this->assertSame(7, $kitInfo['montaveis']); // min(20/2, 7/1)

        $alertas = collect($consulta->alertas(50));
        $this->assertTrue($alertas->contains(fn ($a) => $a['tipo'] === 'Vence em breve' && str_contains($a['titulo'], 'Sabonete')));
        $this->assertTrue($alertas->contains(fn ($a) => $a['tipo'] === 'Estoque baixo' && str_contains($a['titulo'], 'Sabonete')));

        $ocupacao = collect($consulta->ocupacao())->firstWhere('id', $this->almox->id);
        $this->assertSame(27, $ocupacao['saldo']);
        $this->assertSame(3, $ocupacao['percentual']);
    }

    // ── Tela ───────────────────────────────────────────────────────────────

    public function test_tela_lista_lotes_e_cadastra_produto(): void
    {
        $p = $this->produto(['nome' => 'Colchao solteiro', 'categoria' => 'dormitorio', 'patrimonio' => 'PAT-778']);
        $this->entrada($p, 'C1', '2030-01-01', 12);

        $this->get(route('estoque.index'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Estoque/EstoqueIndex')
            ->has('statistics.saldo_total')
            ->has('ocupacao')
            ->has('catalogo'));

        $this->getJson(route('estoque.lotes.lista', ['busca' => 'PAT-778']))->assertOk()
            ->assertJsonPath('data.0.nome', 'Colchao solteiro')
            ->assertJsonPath('data.0.categoria_label', 'Dormitório')
            ->assertJsonPath('data.0.saldo', 12);

        $this->post(route('estoque.produtos.store'), ['sku' => 'novo-1', 'nome' => 'Agua mineral 5L', 'categoria' => 'alimentos'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Produto::where('sku', 'NOVO-1')->exists());

        // Produto com saldo nao sai do catalogo.
        $this->delete(route('estoque.produtos.destroy', $p->id))->assertSessionHasErrors('estoque');
        $this->assertNotSoftDeleted('estoque_produtos', ['id' => $p->id]);
    }

    public function test_tela_registra_saida_para_municipio(): void
    {
        $p = $this->produto();
        $this->entrada($p, 'M1', '2030-01-01', 10);
        $municipio = Municipio::factory()->create();

        $this->post(route('estoque.saidas.store'), [
            'almoxarifado_id' => $this->almox->id, 'destino_tipo' => 'municipio', 'destino_municipio_id' => $municipio->id,
            'itens' => [['produto_id' => $p->id, 'quantidade' => 4]],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(6, Lote::where('codigo', 'M1')->value('saldo'));
        $romaneio = MovimentacaoEstoque::where('produto_id', $p->id)->where('tipo', 'saida')->value('romaneio');
        $this->getJson(route('estoque.romaneios.show', $romaneio))->assertOk()->assertJsonPath('itens.0.quantidade', 4);

        // Municipio obrigatorio quando o destino e municipio.
        $this->post(route('estoque.saidas.store'), [
            'almoxarifado_id' => $this->almox->id, 'destino_tipo' => 'municipio',
            'itens' => [['produto_id' => $p->id, 'quantidade' => 1]],
        ])->assertSessionHasErrors('destino_municipio_id');
    }

    // ── API v1 ─────────────────────────────────────────────────────────────

    public function test_api_inventario_e_movimentacoes_com_error_response(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $p = $this->produto(['nome' => 'Fralda geriatrica', 'categoria' => 'higiene', 'patrimonio' => 'PAT-API-1']);

        $this->postJson('/api/v1/estoque/movimentacoes', [
            'tipo' => 'entrada', 'produto_id' => $p->id, 'almoxarifado_id' => $this->almox->id,
            'lote' => 'API-1', 'validade' => '2030-01-01', 'quantidade' => 8, 'fornecedor' => 'Doacao',
        ])->assertCreated()->assertJsonPath('success', true)->assertJsonPath('data.saldo_apos', 8);

        $this->getJson('/api/v1/inventario?patrimonio=PAT-API-1')->assertOk()
            ->assertJsonPath('data.0.saldo_total', 8)
            ->assertJsonPath('data.0.saldo_disponivel', 8);

        $this->postJson('/api/v1/estoque/movimentacoes', [
            'tipo' => 'saida', 'almoxarifado_id' => $this->almox->id, 'destino_tipo' => 'abrigo', 'destino_descricao' => 'Ginasio',
            'itens' => [['produto_id' => $p->id, 'quantidade' => 9]],
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors' => ['estoque']]);

        $this->postJson('/api/v1/inventario', ['nome' => 'Sem SKU'])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['sku', 'categoria']]);

        $this->postJson('/api/v1/inventario', ['sku' => 'api-novo', 'nome' => 'Lona', 'categoria' => 'outros'])
            ->assertCreated()->assertJsonPath('data.sku', 'API-NOVO');

        $this->getJson('/api/v1/estoque/movimentacoes?produto_id=' . $p->id)->assertOk()->assertJsonPath('data.0.tipo', 'entrada');
    }
}
