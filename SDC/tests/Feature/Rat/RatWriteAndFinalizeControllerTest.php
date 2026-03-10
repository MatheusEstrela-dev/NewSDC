<?php

declare(strict_types=1);

namespace Tests\Feature\Rat;

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Testes de Feature para RatWriteController (update + draft)
 * e RatFinalizeController (finalize).
 */
class RatWriteAndFinalizeControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private const PERMISSIONS = [
        'rat.protocolos.view',
        'rat.protocolos.edit',
        'rat.protocolos.finalize',
    ];

    private function actingAsAdmin(): static
    {
        foreach (self::PERMISSIONS as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        app()['cache']->forget('spatie.permission.cache');

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSIONS);

        return $this->actingAs($user);
    }

    private function makeRat(string $status = 'rascunho'): Rat
    {
        return Rat::create([
            'protocolo'    => 'RAT-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'status'       => $status,
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
            'anexos'       => [],
        ]);
    }

    // -----------------------------------------------------------------------
    // PUT /rat/{id}  (RatWriteController::update)
    // -----------------------------------------------------------------------

    public function test_update_altera_status_para_em_andamento(): void
    {
        $rat = $this->makeRat(Status::RASCUNHO->value);

        $this->actingAsAdmin()
            ->put(route('rat.update', $rat->id), [
                'dadosGerais' => ['data_fato' => '2026-03-01'],
            ])
            ->assertRedirect(route('rat.edit', $rat->id));

        $this->assertSame(Status::EM_ANDAMENTO->value, $rat->fresh()->status);
    }

    public function test_update_persiste_dados_gerais(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()->put(route('rat.update', $rat->id), [
            'dadosGerais' => [
                'data_fato'              => '2026-03-10',
                'nat_nome_operacao'      => 'Operação Teste',
                'data_inicio_atividade'  => '2026-03-10T08:00',
                'data_termino_atividade' => '2026-03-10T12:00',
            ],
        ]);

        $fresco = $rat->fresh();
        $this->assertSame('2026-03-10', $fresco->dados_gerais['data_fato'] ?? null);
        $this->assertSame('Operação Teste', $fresco->dados_gerais['nat_nome_operacao'] ?? null);
    }

    public function test_update_persiste_endereco_com_coordenadas(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()->put(route('rat.update', $rat->id), [
            'endereco' => [
                'logradouro'     => 'Rua XV de Novembro',
                'numero'         => '100',
                'bairro'         => 'Centro',
                'latitude'       => -25.4284,
                'longitude'      => -49.2733,
                'tipo_localizacao' => 'urbana',
            ],
        ]);

        $fresco = $rat->fresh();
        $this->assertEqualsWithDelta(-25.4284, (float) ($fresco->endereco['latitude'] ?? 0), 0.0001);
        $this->assertSame('urbana', $fresco->endereco['tipo_localizacao'] ?? null);
    }

    public function test_update_persiste_comunicacao(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()->put(route('rat.update', $rat->id), [
            'comunicacao' => [
                'tipo_solicitacao' => 'telefone',
                'nome_solicitante' => 'João da Silva',
                'telefone_contato' => '(41) 99999-1234',
            ],
        ]);

        $fresco = $rat->fresh();
        $this->assertSame('telefone', $fresco->comunicacao['tipo_solicitacao'] ?? null);
        $this->assertSame('João da Silva', $fresco->comunicacao['nome_solicitante'] ?? null);
    }

    public function test_update_retorna_422_com_latitude_invalida(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->put(route('rat.update', $rat->id), [
                'endereco' => ['latitude' => 999], // fora do range -90,90
            ])
            ->assertSessionHasErrors('endereco.latitude');
    }

    public function test_update_retorna_422_com_longitude_invalida(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->put(route('rat.update', $rat->id), [
                'endereco' => ['longitude' => -999], // fora do range -180,180
            ])
            ->assertSessionHasErrors('endereco.longitude');
    }

    public function test_update_retorna_422_com_tipo_solicitacao_invalido(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->put(route('rat.update', $rat->id), [
                'comunicacao' => ['tipo_solicitacao' => 'telegrama'], // não está na lista
            ])
            ->assertSessionHasErrors('comunicacao.tipo_solicitacao');
    }

    public function test_update_retorna_422_com_uf_maior_que_2_chars(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->put(route('rat.update', $rat->id), [
                'local' => ['uf' => 'SPX'], // deve ter exatamente 2 chars
            ])
            ->assertSessionHasErrors('local.uf');
    }

    // -----------------------------------------------------------------------
    // PATCH /rat/{id}/draft  (RatWriteController::draft)
    // -----------------------------------------------------------------------

    public function test_draft_nao_altera_status(): void
    {
        $rat = $this->makeRat(Status::RASCUNHO->value);

        $this->actingAsAdmin()->patch(route('rat.draft', $rat->id), [
            'dadosGerais' => ['nat_nome_operacao' => 'Rascunho parcial'],
        ]);

        $this->assertSame(Status::RASCUNHO->value, $rat->fresh()->status);
    }

    public function test_draft_retorna_422_com_tipo_localizacao_invalido(): void
    {
        $rat = $this->makeRat();

        $this->actingAsAdmin()
            ->patch(route('rat.draft', $rat->id), [
                'endereco' => ['tipo_localizacao' => 'espaco_sideral'], // inválido
            ])
            ->assertSessionHasErrors('endereco.tipo_localizacao');
    }

    // -----------------------------------------------------------------------
    // PATCH /rat/{id}/finalize  (RatFinalizeController)
    // -----------------------------------------------------------------------

    public function test_finalize_muda_status_para_finalizado(): void
    {
        $rat = $this->makeRat(Status::EM_ANDAMENTO->value);

        $this->actingAsAdmin()
            ->patch(route('rat.finalize', $rat->id))
            ->assertRedirect(route('rat.show', $rat->id));

        $this->assertSame(Status::FINALIZADO->value, $rat->fresh()->status);
    }

    public function test_finalize_retorna_422_quando_ja_finalizado(): void
    {
        $rat = $this->makeRat(Status::FINALIZADO->value);

        $this->actingAsAdmin()
            ->patch(route('rat.finalize', $rat->id))
            ->assertStatus(422);
    }

    public function test_finalize_retorna_404_quando_id_invalido(): void
    {
        $this->actingAsAdmin()
            ->patch(route('rat.finalize', 'uuid-invalido-xyz'))
            ->assertStatus(404);
    }

    public function test_finalize_exibe_mensagem_de_sucesso(): void
    {
        $rat = $this->makeRat(Status::EM_ANDAMENTO->value);

        $this->actingAsAdmin()
            ->patch(route('rat.finalize', $rat->id))
            ->assertRedirect(route('rat.show', $rat->id))
            ->assertSessionHas('success');
    }
}
