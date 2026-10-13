<?php

declare(strict_types=1);

namespace Tests\Feature\Dto;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\User;
use App\Modules\Acessos\Models\CadastroAcesso;
use App\Modules\Cedec\DTOs\PrefeituraFiltroDTO;
use App\Modules\Cemaden\DTOs\LeituraCemadenDTO;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\DTOs\FiltroDemanda;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Inventario\DTOs\EmprestimoAvulsoData;
use App\Modules\Inventario\DTOs\EquipamentoData;
use App\Modules\Inventario\Services\EquipamentoService;
use App\Modules\Inventario\Services\MovimentacaoService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * P1 e P2 da auditoria DTO (#63): escrita via DTO + Service em Acessos e
 * Inventario, factories sem Request em Demandas/Cedec e leitura de feed que
 * nao transforma texto invalido em medicao zero.
 */
class AuditoriaDtoFluxoEscritaTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());

        return $user;
    }

    // ── Acessos (Controller -> DTO -> Service) ──────────────────────────────

    public function test_cadastro_de_acesso_passa_pelo_service_com_auditoria_e_regras(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $solicitante = $this->admin();

        $this->actingAs($solicitante)->post(route('acessos.store'), [
            'nome' => 'Fulano de Tal',
            'tipo_documento' => 'masp',
            'documento' => '1234567',
            'cpf' => '52998224725',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cadastro = CadastroAcesso::where('cpf', '52998224725')->firstOrFail();
        $this->assertSame((int) $solicitante->id, (int) $cadastro->solicitado_por_id);
        $this->assertTrue($cadastro->auditoria()->where('acao', 'criado')->exists());

        // Quem solicitou nao aprova o proprio cadastro.
        $this->actingAs($solicitante)->post(route('acessos.aprovar', $cadastro))->assertForbidden();

        // Transicao invalida (pendente -> ativo) e barrada.
        $this->actingAs($solicitante)->post(route('acessos.status', $cadastro), ['status' => 'ativo'])->assertStatus(409);

        // Outro usuario aprova.
        $this->actingAs($this->admin())->post(route('acessos.aprovar', $cadastro))->assertRedirect();
        $this->assertSame('aprovado', $cadastro->fresh()->status);
    }

    // ── Inventario ──────────────────────────────────────────────────────────

    public function test_emprestimo_aceita_quantidade_vinda_como_texto(): void
    {
        $operador = User::factory()->create();
        $destino = User::factory()->create();

        $equipamento = app(EquipamentoService::class)->criar(EquipamentoData::fromArray([
            'nome' => 'Notebook', 'patrimonio' => 'PAT-' . random_int(10000, 99999),
            'emprestavel' => '1', 'quantidade' => '1',
        ]));

        // "1" de um envio por formulario: a comparacao estrita do Service
        // recusava sempre (string !== int).
        $mov = app(MovimentacaoService::class)->registrar(EmprestimoAvulsoData::fromArray([
            'equipamento_id' => (string) $equipamento->id,
            'tipo' => 'emprestimo',
            'quantidade' => '1',
            'usuario_destino_id' => (string) $destino->id,
        ]), (int) $operador->id);

        $this->assertSame(1, (int) $mov->quantidade);
        $this->assertSame((int) $destino->id, (int) $equipamento->fresh()->user_id);
    }

    // ── Demandas e Cedec: factories sem Request ─────────────────────────────

    public function test_criar_demanda_usa_o_autor_como_solicitante_por_padrao(): void
    {
        $dados = CriarDemandaData::fromValidated(['titulo' => 'Impressora', 'descricao' => 'Nao imprime'], 42);

        $this->assertSame(42, $dados->solicitanteId);
        $this->assertSame(42, $dados->criadoPorId);
    }

    public function test_filtros_vem_de_array_validado(): void
    {
        $this->assertArrayHasKey('status', FiltroDemanda::regras());
        $this->assertSame(7, FiltroDemanda::fromValidated(['assunto_id' => '7'])->assuntoId);

        $filtro = PrefeituraFiltroDTO::fromArray(['per_page' => '999', 'pendencia' => 'invalida', 'busca' => 'Ouro']);
        $this->assertSame(20, $filtro->perPage, 'per_page fora da lista cai no padrao');
        $this->assertNull($filtro->pendencia);
        $this->assertSame('Ouro', $filtro->busca);
    }

    // ── Feeds (Cemaden e Inmet) ─────────────────────────────────────────────

    public function test_leitura_invalida_do_feed_vira_ausencia_e_nao_zero(): void
    {
        $agora = Carbon::now();

        $this->assertNull(LeituraCemadenDTO::fromFeedArray(['codestacao' => 'X1', 'acumulado' => 'n/d'], $agora)->acumulado24h);
        $this->assertSame(12.5, LeituraCemadenDTO::fromFeedArray(['codestacao' => 'X1', 'acumulado' => '12,5'], $agora)->acumulado24h);
        $this->assertSame(0.0, LeituraCemadenDTO::fromFeedArray(['codestacao' => 'X1', 'acumulado' => '0'], $agora)->acumulado24h);

        $inmet = LeituraMeteorologicaDTO::fromInmetArray([
            'CD_ESTACAO' => 'A001', 'DT_MEDICAO' => '2026-10-13', 'HR_MEDICAO' => '1200',
            'TEM_INS' => '-', 'UMD_INS' => '65', 'CHUVA' => '',
        ]);
        $this->assertNull($inmet->temperatura, '"-" do INMET nao e 0 grau');
        $this->assertSame(65.0, $inmet->umidade);
        $this->assertNull($inmet->precipitacao);
    }
}
