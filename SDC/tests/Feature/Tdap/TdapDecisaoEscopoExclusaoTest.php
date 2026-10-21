<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Services\CronoViagemService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ordem das decisoes sobre a viagem (municipio antes da CEDEC), recorte do
 * COMPDEC por municipio e cronograma com entrega que nao pode ser excluido.
 */
class TdapDecisaoEscopoExclusaoTest extends TestCase
{
    use DatabaseTransactions;

    private User $cedec;
    private int $municipioId;
    private int $cronogramaId;
    private int $ccId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->cedec = User::factory()->create();
        $this->cedec->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $this->cedec = $this->cedec->fresh();
        $this->actingAs($this->cedec);

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $this->municipioId = Municipio::factory()->create()->id;
        $ata = DB::table('tdap_atas')->insertGetId(['numero' => 'DE-' . random_int(1000, 9999), 'dt_inicio' => now()->subMonth()->toDateString(), 'dt_final' => now()->addYear()->toDateString()] + $agora);
        $prestador = DB::table('tdap_prestadores')->insertGetId(['cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador DE', 'email' => 'de@t.local'] + $agora);
        $lote = DB::table('tdap_lotes')->insertGetId(['ata_id' => $ata, 'prestador_id' => $prestador, 'numero' => 'L-DE', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        $caminhao = DB::table('tdap_caminhoes')->insertGetId(['prestador_id' => $prestador, 'placa' => 'DEC' . random_int(1, 9) . 'A' . random_int(10, 99), 'capacidade_m3' => 8, 'ativo' => true] + $agora);
        $this->cronogramaId = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-DE-' . random_int(100, 999), 'ata_id' => $ata, 'lote_id' => $lote, 'municipio_id' => $this->municipioId,
            'prestador_id' => $prestador, 'dt_inicio' => now()->subWeek()->toDateString(), 'dt_final' => now()->addMonth()->toDateString(),
            'ativo' => true, 'ativado_em' => now(),
        ] + $agora);
        $this->ccId = DB::table('tdap_crono_caminhoes')->insertGetId(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $caminhao] + $agora);
    }

    private function viagem(?int $validado = null, ?int $confirmado = null): CronoViagem
    {
        $id = DB::table('tdap_crono_viagens')->insertGetId([
            'crono_caminhao_id' => $this->ccId, 'data_registro' => now()->subDay(),
            'validado' => $validado, 'confirmado' => $confirmado, 'confirmado_em' => $confirmado !== null ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return CronoViagem::findOrFail($id);
    }

    /** Usuario do papel informado, lotado num COMPDEC do municipio informado. */
    private function lotadoEm(int $municipioId, string $papel): User
    {
        $orgao = Orgao::factory()->compdec()->create(['municipio_id' => $municipioId]);
        $user = User::factory()->create(['orgao_principal_id' => $orgao->id]);
        $user->assignRole(Role::where('slug', $papel)->firstOrFail());

        return $user->fresh();
    }

    public function test_cedec_nao_aprova_viagem_sem_confirmacao_do_municipio(): void
    {
        config(['tdap.viagens.exige_confirmacao_municipio' => true]);
        $viagem = $this->viagem();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Aguardando confirmacao do municipio.');

        app(CronoViagemService::class)->validar($viagem->id, true, null, $this->cedec);
    }

    public function test_com_a_chave_desligada_cedec_aprova_sem_esperar_o_municipio(): void
    {
        config(['tdap.viagens.exige_confirmacao_municipio' => false]);
        $viagem = $this->viagem();

        $aprovada = app(CronoViagemService::class)->validar($viagem->id, true, null, $this->cedec);

        $this->assertSame(CronoViagem::STATUS_APROVADA, $aprovada->validado);
    }

    public function test_viagem_confirmada_pode_ser_aprovada_e_rejeitar_vale_sempre(): void
    {
        config(['tdap.viagens.exige_confirmacao_municipio' => true]);
        $service = app(CronoViagemService::class);

        $confirmada = $this->viagem(confirmado: CronoViagem::CONFIRMACAO_CONFIRMADA);
        $this->assertSame(CronoViagem::STATUS_APROVADA, $service->validar($confirmada->id, true, null, $this->cedec)->validado);

        $semDecisao = $this->viagem();
        $this->assertSame(CronoViagem::STATUS_REJEITADA, $service->validar($semDecisao->id, false, 'nao houve', $this->cedec)->validado);
    }

    public function test_municipio_nao_reprova_viagem_que_a_cedec_ja_validou(): void
    {
        config(['tdap.viagens.exige_confirmacao_municipio' => true]);
        $validada = $this->viagem(validado: CronoViagem::STATUS_APROVADA);
        $compdec = $this->lotadoEm($this->municipioId, 'compdec');

        $resultado = app(CronoViagemService::class)->reprovarEmLote($compdec, [$validada->id], 'nao chegou');

        $this->assertSame(0, $resultado['reprovadas']);
        $this->assertNull($validada->fresh()->confirmado);
    }

    public function test_reprovacao_do_municipio_entra_no_historico(): void
    {
        $viagem = $this->viagem();
        $compdec = $this->lotadoEm($this->municipioId, 'compdec');

        app(CronoViagemService::class)->reprovarEmLote($compdec, [$viagem->id], 'nao chegou');

        $this->assertDatabaseHas('tdap_historicos', [
            'entity_type' => 'viagem', 'entity_id' => $viagem->id, 'tipo_evento' => 'viagem.reprovada_municipio',
        ]);
    }

    public function test_compdec_nao_abre_cronograma_de_outro_municipio(): void
    {
        $deFora = $this->lotadoEm(Municipio::factory()->create()->id, 'compdec');

        $this->actingAs($deFora)->get(route('tdap.cronogramas.show', $this->cronogramaId))->assertForbidden();
        $this->actingAs($deFora)->getJson(route('tdap.cronogramas.impressao', $this->cronogramaId))->assertForbidden();
    }

    public function test_listagem_do_compdec_so_traz_o_proprio_municipio(): void
    {
        $deFora = Municipio::factory()->create()->id;

        $doMunicipio = app(CronogramaService::class)->listar(50, [], $this->municipioId);
        $deOutro = app(CronogramaService::class)->listar(50, ['municipio_id' => $this->municipioId], $deFora);

        $this->assertTrue($doMunicipio->getCollection()->contains('id', $this->cronogramaId));
        $this->assertFalse($deOutro->getCollection()->contains('id', $this->cronogramaId), 'o filtro da tela nao fura o escopo');
    }

    public function test_validar_viagem_fora_do_escopo_e_recusado(): void
    {
        config(['tdap.viagens.exige_confirmacao_municipio' => false]);
        $viagem = $this->viagem();
        $gestorDeFora = $this->lotadoEm(Municipio::factory()->create()->id, 'manager');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Viagem fora do seu municipio.');

        app(CronoViagemService::class)->validar($viagem->id, true, null, $gestorDeFora);
    }

    public function test_cronograma_encerrado_nao_pode_ser_excluido(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['ativo' => false, 'encerrado_em' => now()]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Arquive em vez de excluir.');

        app(CronogramaService::class)->deletar($this->cronogramaId);
    }

    public function test_rascunho_com_viagem_aprovada_nao_pode_ser_excluido(): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update(['ativo' => false]);
        $this->viagem(validado: CronoViagem::STATUS_APROVADA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('viagem aprovada');

        app(CronogramaService::class)->deletar($this->cronogramaId);
    }
}
