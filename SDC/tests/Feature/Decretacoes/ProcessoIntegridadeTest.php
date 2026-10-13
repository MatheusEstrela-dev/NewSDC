<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Decretacoes\Enums\StatusProcesso;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Integridade de criacao e edicao de processos de decretacao (#29) e as
 * regressoes da camada DTO (#66): status validado, update parcial que nao
 * apaga municipios e campo limpo de verdade.
 *
 * Ambiente: DatabaseTransactions -- cada teste roda numa transacao desfeita
 * no fim, entao nada fica no banco depois da suite.
 */
class ProcessoIntegridadeTest extends TestCase
{
    use DatabaseTransactions;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->actingAs($user);

        $this->municipio = Municipio::factory()->create();
    }

    /** @return array<string, mixed> campos obrigatorios do StoreProcessoRequest */
    private function obrigatorios(array $extra = []): array
    {
        // $extra a esquerda: no `+` do PHP vence a chave do primeiro operando.
        return $extra + [
            'data_entrada' => '2026-03-01',
            'origem'       => 'municipal',
            'municipio_id' => $this->municipio->id,
        ];
    }

    private function criar(array $extra = []): Processo
    {
        $this->post(route('decretacoes.store'), $this->obrigatorios($extra))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return Processo::query()->latest('id')->firstOrFail();
    }

    // ── Criacao (store) ─────────────────────────────────────────────────────

    public function test_cria_com_os_campos_obrigatorios_e_vincula_o_municipio(): void
    {
        $processo = $this->criar([
            'situacao_anormalidade' => 'SE',
            'n_decreto_municipal'   => '123/2026',
        ]);

        $this->assertSame('2026-03-01', $processo->data_entrada?->format('Y-m-d') ?? substr((string) $processo->getRawOriginal('data_entrada'), 0, 10));
        $this->assertSame('MUNICIPAL', $processo->processo);
        $this->assertSame('SE', $processo->tipo_desastre);
        $this->assertSame('123/2026', $processo->decreto_municipal);
        $this->assertNotNull($processo->created_by, 'created_by vem do usuario autenticado');
        $this->assertSame(180, (int) $processo->prazo_vigencia, 'prazo em branco assume o padrao legal');

        // Integridade do relacionamento: o vinculo aponta para processo e municipio reais.
        $vinculo = DecretoMunicipio::where('entrada_processos_id', $processo->id)->firstOrFail();
        $this->assertSame($this->municipio->id, (int) $vinculo->municipio_id);
    }

    public function test_status_inicial_e_registro(): void
    {
        $this->assertSame(StatusProcesso::REGISTRO->value, $this->criar()->status);
    }

    public function test_status_escolhido_na_criacao_e_respeitado(): void
    {
        $processo = $this->criar(['status' => StatusProcesso::EM_ANALISE_ESTADO->value]);

        $this->assertSame(StatusProcesso::EM_ANALISE_ESTADO->value, $processo->status);
    }

    public function test_criacao_falha_sem_campos_obrigatorios(): void
    {
        $antes = Processo::count();

        $this->post(route('decretacoes.store'), [])
            ->assertSessionHasErrors(['data_entrada', 'origem', 'municipio_id']);

        $this->post(route('decretacoes.store'), $this->obrigatorios(['data_entrada' => 'nao-e-data']))
            ->assertSessionHasErrors(['data_entrada']);

        $this->assertSame($antes, Processo::count(), 'nada pode ser gravado quando a validacao falha');
    }

    public function test_criacao_recusa_status_fora_do_enum(): void
    {
        $antes = Processo::count();

        $this->post(route('decretacoes.store'), $this->obrigatorios(['status' => 'pendente']))
            ->assertSessionHasErrors(['status']);

        $this->assertSame($antes, Processo::count());
    }

    // ── Edicao (update) ─────────────────────────────────────────────────────

    public function test_edicao_altera_campos_permitidos_sem_mudar_id_e_atualiza_updated_at(): void
    {
        $processo = $this->criar(['n_decreto_municipal' => 'ANTIGO']);
        $idOriginal = $processo->id;
        $updatedAntes = $processo->updated_at;

        $this->travel(5)->minutes();

        $this->put(route('decretacoes.update', $processo->id), [
            'n_decreto_municipal' => 'NOVO-456',
            'observacoes'         => 'Revisado pela CEDEC',
        ])->assertSessionHasNoErrors();

        $processo->refresh();
        $this->assertSame($idOriginal, $processo->id);
        $this->assertSame('NOVO-456', $processo->decreto_municipal);
        $this->assertSame('Revisado pela CEDEC', $processo->observacoes);
        $this->assertTrue($processo->updated_at->gt($updatedAntes), 'updated_at deve avancar');
    }

    public function test_edicao_sem_municipio_mantem_os_vinculos(): void
    {
        $processo = $this->criar();

        // Antes: municipio ausente virava [] e o sync removia todos os vinculos.
        $this->put(route('decretacoes.update', $processo->id), ['observacoes' => 'so isto'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$this->municipio->id],
            DecretoMunicipio::where('entrada_processos_id', $processo->id)->pluck('municipio_id')->map('intval')->all(),
        );
    }

    public function test_edicao_limpa_campo_enviado_vazio_e_preserva_o_ausente(): void
    {
        $processo = $this->criar(['n_decreto_municipal' => 'D-1', 'observacoes' => 'manter']);

        $this->put(route('decretacoes.update', $processo->id), ['n_decreto_municipal' => ''])
            ->assertSessionHasNoErrors();

        $processo->refresh();
        $this->assertNull($processo->decreto_municipal);
        $this->assertSame('manter', $processo->observacoes);
    }

    public function test_edicao_valida_o_status(): void
    {
        $processo = $this->criar();

        $this->put(route('decretacoes.update', $processo->id), ['status' => 'qualquer coisa'])
            ->assertSessionHasErrors(['status']);
        $this->assertSame(StatusProcesso::REGISTRO->value, $processo->fresh()->status);

        $this->put(route('decretacoes.update', $processo->id), ['status' => StatusProcesso::AGUARDANDO_NOTA_JURIDICA->value])
            ->assertSessionHasNoErrors();
        $this->assertSame(StatusProcesso::AGUARDANDO_NOTA_JURIDICA->value, $processo->fresh()->status);
    }

    public function test_edicao_de_processo_com_status_legado_continua_possivel(): void
    {
        $processo = $this->criar();
        $processo->forceFill(['status' => 'pendente'])->saveQuietly();

        // O formulario reenvia o status gravado; ele precisa passar mesmo fora do enum.
        $this->put(route('decretacoes.update', $processo->id), ['status' => 'pendente', 'observacoes' => 'ok'])
            ->assertSessionHasNoErrors();

        $this->assertSame('ok', $processo->fresh()->observacoes);
    }
}
