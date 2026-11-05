<?php

declare(strict_types=1);

namespace Tests\Feature\Tdap;

use App\Models\Municipio;
use App\Models\Role;
use App\Models\User;
use App\Modules\Tdap\Domain\Exceptions\ViagemForaDoLimiteException;
use App\Modules\Tdap\DTOs\CaminhaoDTO;
use App\Modules\Tdap\DTOs\CronoCaminhaoDTO;
use App\Modules\Tdap\DTOs\CronogramaDTO;
use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\DTOs\LoteDTO;
use App\Modules\Tdap\DTOs\VistoriaDTO;
use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronogramaComprovante;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Models\VistoriaFoto;
use App\Modules\Tdap\Requests\StoreCronoCaminhaoRequest;
use App\Modules\Tdap\Requests\StoreCronoViagemRequest;
use App\Modules\Tdap\Requests\UpdateCronoCaminhaoRequest;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Services\CronogramaComprovanteService;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Services\CronoViagemService;
use App\Modules\Tdap\Services\FrotaService;
use App\Modules\Tdap\Services\LoteService;
use App\Modules\Tdap\Services\TramitacaoCronogramaService;
use App\Modules\Tdap\Services\VistoriaFotoService;
use App\Modules\Tdap\Services\VistoriaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Correcoes de regra de negocio do TDAP (R1-R3, N1-N12, F1, F5, P3 e
 * comunidade da alocacao). Um teste (ou par) por achado, no servico; a corrida
 * em si nao se reproduz com uma conexao, entao as travas sao conferidas pelo
 * SELECT ... FOR UPDATE no log de SQL, como em TdapAlocacaoTravadaNaTramitacaoTest.
 */
class TdapRegrasDeNegocioCorrecoesTest extends TestCase
{
    use DatabaseTransactions;

    private int $municipioId;
    private int $ataId;
    private int $prestadorId;
    private int $loteId;
    private int $caminhaoId;
    private int $cronogramaId;
    private int $alocacaoId;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'tdap.cronogramas.exige_tramite'           => true,
            'tdap.cronogramas.segregacao_funcoes'      => false,
            'tdap.ponto_captacao.exige_pmda_aprovado'  => true,
        ]);

        $agora = ['created_at' => now(), 'updated_at' => now()];
        $this->municipioId = Municipio::factory()->create()->id;
        $this->ataId = DB::table('tdap_atas')->insertGetId(['numero' => 'RN-'.random_int(1000, 9999), 'dt_inicio' => now()->subMonth()->toDateString(), 'dt_final' => now()->addYear()->toDateString()] + $agora);
        $this->prestadorId = $this->novoPrestador();
        $this->loteId = DB::table('tdap_lotes')->insertGetId(['ata_id' => $this->ataId, 'prestador_id' => $this->prestadorId, 'numero' => 'L-RN', 'qtd_agua_m3' => 1000, 'valor_m3' => 10] + $agora);
        DB::table('tdap_lote_municipios')->insert(['lote_id' => $this->loteId, 'municipio_id' => $this->municipioId]);
        $this->caminhaoId = $this->novoCaminhao();
        $this->vistoria($this->caminhaoId, now()->toDateString());
        $this->cronogramaId = DB::table('tdap_cronogramas')->insertGetId([
            'numero' => 'CR-RN-'.random_int(1000, 9999), 'ata_id' => $this->ataId, 'lote_id' => $this->loteId,
            'municipio_id' => $this->municipioId, 'prestador_id' => $this->prestadorId,
            'dt_inicio' => now()->subDays(5)->toDateString(), 'dt_final' => now()->addMonth()->toDateString(), 'ativo' => false,
        ] + $agora);
        $this->alocacaoId = DB::table('tdap_crono_caminhoes')->insertGetId(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $this->caminhaoId] + $agora);
    }

    /* ------------------------------------------------------------ helpers */

    private function novoPrestador(): int
    {
        return DB::table('tdap_prestadores')->insertGetId([
            'cnpj' => (string) random_int(10000000000000, 99999999999999), 'nome' => 'Prestador RN '.random_int(1, 999),
            'email' => 'rn@t.local', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function novoCaminhao(?int $prestadorId = null): int
    {
        return DB::table('tdap_caminhoes')->insertGetId([
            'prestador_id' => $prestadorId ?? $this->prestadorId, 'placa' => 'RNZ'.random_int(1, 9).'B'.random_int(10, 99),
            'capacidade_m3' => 8, 'ativo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function vistoria(int $caminhaoId, string $data, string $parecer = 'aprovada'): int
    {
        return DB::table('tdap_vistorias')->insertGetId([
            'nome' => 'Vistoriador', 'placa_id' => $caminhaoId, 'data' => $data, 'capacidade' => 8,
            'parecer' => $parecer, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function cronograma(array $campos): void
    {
        DB::table('tdap_cronogramas')->where('id', $this->cronogramaId)->update($campos);
    }

    private function emExecucao(): void
    {
        $this->cronograma(['ativo' => true, 'ativado_em' => now(), 'fase_tramite' => 'em_execucao']);
    }

    private function viagem(?int $validado, ?string $data = null): int
    {
        return DB::table('tdap_crono_viagens')->insertGetId([
            'crono_caminhao_id' => $this->alocacaoId, 'data_registro' => $data ?? now()->subDay(),
            'validado' => $validado, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function dtoCronograma(array $troca = []): CronogramaDTO
    {
        $c = Cronograma::findOrFail($this->cronogramaId);

        return CronogramaDTO::fromRequest($troca + [
            'numero' => $c->numero, 'ata_id' => $c->ata_id, 'lote_id' => $c->lote_id, 'municipio_id' => $c->municipio_id,
            'prestador_id' => $c->prestador_id, 'consumo_diario' => 100, 'dias' => 10,
            'dt_inicio' => $c->dt_inicio->toDateString(), 'dt_final' => $c->dt_final->toDateString(),
            'ponto_captacao_ids' => [],
        ]);
    }

    private function dtoCaminhao(array $troca = []): CaminhaoDTO
    {
        $c = DB::table('tdap_caminhoes')->find($this->caminhaoId);

        return new CaminhaoDTO(
            $troca['prestador_id'] ?? (int) $c->prestador_id,
            $c->placa, null, null, null, null,
            $troca['capacidade_m3'] ?? (float) $c->capacidade_m3,
            array_key_exists('ativo', $troca) ? $troca['ativo'] : true,
            $troca['observacoes'] ?? null,
        );
    }

    private function dtoVistoria(int $vistoriaId, array $troca = []): VistoriaDTO
    {
        $v = Vistoria::findOrFail($vistoriaId);

        return VistoriaDTO::fromRequest($troca + [
            'nome' => $v->nome, 'placa_id' => $v->placa_id, 'data' => $v->data->toDateString(),
            'capacidade' => 8, 'parecer' => $v->parecer->value,
        ]);
    }

    /** @return array<int, string> */
    private function sqlDe(callable $acao): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $acao();
        } finally {
            $log = array_column(DB::getQueryLog(), 'query');
            DB::disableQueryLog();
        }

        return $log;
    }

    /** Indice da primeira consulta FOR UPDATE na tabela, ou null. */
    private function indiceDaTrava(array $sql, string $tabela): ?int
    {
        return array_key_first(array_filter(
            $sql,
            fn (string $q) => str_contains($q, "from \"{$tabela}\"") && str_contains(strtolower($q), 'for update'),
        ));
    }

    private function esperaDominio(callable $acao, string $trecho): void
    {
        try {
            $acao();
            $this->fail("esperava DomainException com '{$trecho}'");
        } catch (\DomainException $e) {
            $this->assertStringContainsString($trecho, $e->getMessage());
        }
    }

    private function esperaValidacao(callable $acao, string $campo): void
    {
        try {
            $acao();
            $this->fail("esperava ValidationException em '{$campo}'");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($campo, $e->errors());
        }
    }

    /* ----------------------------------------------- R1 / N3 atualizar */

    public function test_r1_atualizar_trava_o_cronograma_antes_de_gravar(): void
    {
        $sql = $this->sqlDe(fn () => app(CronogramaService::class)->atualizar($this->cronogramaId, $this->dtoCronograma(['observacao' => 'x'])));

        $trava = $this->indiceDaTrava($sql, 'tdap_cronogramas');
        $this->assertNotNull($trava, 'esperava SELECT ... FOR UPDATE em tdap_cronogramas');
        $update = array_key_first(array_filter($sql, fn ($q) => str_starts_with($q, 'update "tdap_cronogramas"')));
        $this->assertLessThan($update, $trava);
        $this->assertSame('x', Cronograma::find($this->cronogramaId)->observacao);
    }

    public function test_n3_atualizar_recusa_trocar_prestador_com_caminhao_alocado(): void
    {
        $outro = $this->novoPrestador();

        $this->esperaDominio(
            fn () => app(CronogramaService::class)->atualizar($this->cronogramaId, $this->dtoCronograma(['prestador_id' => $outro])),
            'nao pode trocar de prestador ou de lote',
        );
        $this->assertSame($this->prestadorId, Cronograma::find($this->cronogramaId)->prestador_id);
    }

    public function test_n3_atualizar_troca_prestador_sem_caminhao_alocado(): void
    {
        DB::table('tdap_crono_caminhoes')->where('id', $this->alocacaoId)->update(['deleted_at' => now()]);
        $outro = $this->novoPrestador();

        app(CronogramaService::class)->atualizar($this->cronogramaId, $this->dtoCronograma(['prestador_id' => $outro]));

        $this->assertSame($outro, Cronograma::find($this->cronogramaId)->prestador_id);
    }

    public function test_n3_pode_ativar_recusa_caminhao_de_outra_empresa(): void
    {
        $this->cronograma(['fase_tramite' => 'aprovado']);
        $estranho = $this->novoCaminhao($this->novoPrestador());
        $this->vistoria($estranho, now()->toDateString());
        DB::table('tdap_crono_caminhoes')->insert(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $estranho, 'created_at' => now(), 'updated_at' => now()]);

        [$ok, $erro] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertFalse($ok);
        $this->assertStringContainsString('nao pertence a empresa contratada', (string) $erro);
    }

    public function test_pode_ativar_continua_liberando_o_caso_valido(): void
    {
        $this->cronograma(['fase_tramite' => 'aprovado']);

        $this->assertSame([true, null], app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId)));
    }

    /* ------------------------------------------------------- F1 pontos */

    private function vincularPontoForaDoPmda(): void
    {
        $ponto = DB::table('pip_pmda_ponto')->insertGetId(['municipio_id' => $this->municipioId, 'nome' => 'Poco legado', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tdap_cronograma_ponto')->insert(['cronograma_id' => $this->cronogramaId, 'ponto_id' => $ponto, 'pmda_plano_id' => null, 'created_at' => now(), 'updated_at' => now()]);
        $this->cronograma(['fase_tramite' => 'aprovado']);
    }

    public function test_f1_ativacao_recusa_ponto_fora_do_pmda_vigente(): void
    {
        $this->vincularPontoForaDoPmda();

        [$ok, $erro] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertFalse($ok);
        $this->assertStringContainsString('PMDA', (string) $erro);
        $this->assertStringContainsString('antes de ativar', (string) $erro);
    }

    public function test_f1_flag_desligada_nao_confere_pmda_na_ativacao(): void
    {
        config(['tdap.ponto_captacao.exige_pmda_aprovado' => false]);
        $this->vincularPontoForaDoPmda();

        [$ok] = app(CronogramaService::class)->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertTrue($ok);
    }

    /* ---------------------------------------------- F5 aviso na aprovacao */

    public function test_f5_aprovar_mostra_aviso_do_que_a_ativacao_exige_sem_bloquear(): void
    {
        DB::table('tdap_vistorias')->where('placa_id', $this->caminhaoId)->delete();
        $this->cronograma(['fase_tramite' => 'em_analise']);
        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());

        $acoes = app(TramitacaoCronogramaService::class)->acoesDisponiveis(Cronograma::findOrFail($this->cronogramaId), $user->fresh());
        $aprovar = collect($acoes)->firstWhere('value', AcaoTramite::Aprovar->value);

        $this->assertNotNull($aprovar);
        $this->assertNull($aprovar['bloqueio']);
        $this->assertStringContainsString('nao tem vistoria aprovada', (string) $aprovar['aviso']);
        $this->assertTrue(collect($acoes)->every(fn ($a) => array_key_exists('aviso', $a)));
    }

    /* ------------------------------------------- N1 alocar em ativo */

    public function test_n1_alocar_em_cronograma_ativo_exige_vistoria(): void
    {
        $this->emExecucao();
        $novo = $this->novoCaminhao();

        $this->esperaDominio(
            fn () => app(CronoCaminhaoService::class)->alocar(new CronoCaminhaoDTO($this->cronogramaId, $novo, null, 16.0, 2, 1)),
            'nao tem vistoria aprovada',
        );
    }

    public function test_n1_alocar_em_ativo_recusa_vistoria_que_vence_antes_do_fim(): void
    {
        $this->emExecucao();
        $novo = $this->novoCaminhao();
        // Vence em ~15 dias; o cronograma vai ate daqui a 1 mes.
        $this->vistoria($novo, now()->subMonths(12)->addDays(15)->toDateString());

        $this->esperaDominio(
            fn () => app(CronoCaminhaoService::class)->alocar(new CronoCaminhaoDTO($this->cronogramaId, $novo, null, 16.0, 2, 1)),
            'Renove a vistoria',
        );
    }

    public function test_n1_alocar_em_ativo_com_vistoria_que_cobre_o_periodo(): void
    {
        $this->emExecucao();
        $novo = $this->novoCaminhao();
        $this->vistoria($novo, now()->toDateString());

        $cc = app(CronoCaminhaoService::class)->alocar(new CronoCaminhaoDTO($this->cronogramaId, $novo, null, 16.0, 2, 1));

        $this->assertSame($novo, (int) $cc->caminhao_id);
    }

    public function test_n1_rascunho_continua_alocando_sem_vistoria(): void
    {
        $novo = $this->novoCaminhao();

        $cc = app(CronoCaminhaoService::class)->alocar(new CronoCaminhaoDTO($this->cronogramaId, $novo, null, 16.0, 2, 1));

        $this->assertSame($novo, (int) $cc->caminhao_id);
    }

    /* ---------------------------------------- R3 / N5 registrar viagem */

    public function test_r3_registrar_trava_cronograma_e_depois_a_alocacao(): void
    {
        $this->emExecucao();

        $sql = $this->sqlDe(fn () => app(CronoViagemService::class)->registrar(new CronoViagemDTO($this->alocacaoId, now()->subHour()->toDateTimeString(), null)));

        $cronograma = $this->indiceDaTrava($sql, 'tdap_cronogramas');
        $alocacao = $this->indiceDaTrava($sql, 'tdap_crono_caminhoes');
        $insert = array_key_first(array_filter($sql, fn ($q) => str_starts_with($q, 'insert into "tdap_crono_viagens"')));
        $this->assertNotNull($cronograma);
        $this->assertNotNull($alocacao);
        $this->assertLessThan($alocacao, $cronograma, 'ordem: cronograma antes da alocacao');
        $this->assertLessThan($insert, $alocacao);
    }

    public function test_r3_registrar_recusa_alocacao_removida(): void
    {
        $this->emExecucao();
        DB::table('tdap_crono_caminhoes')->where('id', $this->alocacaoId)->update(['deleted_at' => now()]);

        $this->esperaDominio(
            fn () => app(CronoViagemService::class)->registrar(new CronoViagemDTO($this->alocacaoId, now()->toDateTimeString(), null)),
            'nao encontrada ou removida',
        );
    }

    public function test_r3_registrar_recusa_cronograma_encerrado(): void
    {
        $this->cronograma(['ativo' => true, 'encerrado_em' => now()]);

        $this->esperaDominio(
            fn () => app(CronoViagemService::class)->registrar(new CronoViagemDTO($this->alocacaoId, now()->toDateTimeString(), null)),
            'encerrado',
        );
    }

    public function test_n5_registrar_recusa_data_futura(): void
    {
        $this->emExecucao();

        $this->expectException(ViagemForaDoLimiteException::class);
        $this->expectExceptionMessage('nao pode ser futura');

        app(CronoViagemService::class)->registrar(new CronoViagemDTO($this->alocacaoId, now()->addDay()->setTime(8, 0)->toDateTimeString(), null));
    }

    public function test_n5_registrar_aceita_hoje_em_qualquer_hora(): void
    {
        $this->emExecucao();

        $viagem = app(CronoViagemService::class)->registrar(new CronoViagemDTO($this->alocacaoId, now()->setTime(23, 59)->toDateTimeString(), null));

        $this->assertNotNull($viagem->id);
    }

    public function test_n5_request_recusa_data_futura_e_aceita_hoje(): void
    {
        $regras = (new StoreCronoViagemRequest())->rules();
        $mensagens = (new StoreCronoViagemRequest())->messages();

        $amanha = Validator::make(['crono_caminhao_id' => $this->alocacaoId, 'data_registro' => now()->addDay()->format('Y-m-d\T08:00')], $regras, $mensagens);
        $this->assertTrue($amanha->fails());
        $this->assertSame('A data da viagem nao pode ser futura.', $amanha->errors()->first('data_registro'));

        $hoje = Validator::make(['crono_caminhao_id' => $this->alocacaoId, 'data_registro' => now()->format('Y-m-d\T23:59')], $regras, $mensagens);
        $this->assertFalse($hoje->fails(), (string) $hoje->errors()->first());
    }

    /* ------------------------------------------- N12 remover viagem */

    public function test_n12_remover_viagem_aprovada_explica_sem_mandar_rejeitar(): void
    {
        $id = $this->viagem(CronoViagem::STATUS_APROVADA);

        try {
            app(CronoViagemService::class)->remover($id);
            $this->fail('esperava DomainException');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('ja conta na agua entregue', $e->getMessage());
            $this->assertStringNotContainsString('Rejeite', $e->getMessage());
        }
        $this->assertNotNull(CronoViagem::find($id));
    }

    public function test_n12_remover_viagem_pendente_trava_e_remove(): void
    {
        $id = $this->viagem(null);

        $sql = $this->sqlDe(fn () => $this->assertTrue(app(CronoViagemService::class)->remover($id)));

        $this->assertNotNull($this->indiceDaTrava($sql, 'tdap_crono_viagens'));
        $this->assertNull(CronoViagem::find($id));
    }

    /* --------------------------------------------- R2 / N2 frota e lote */

    public function test_r2_frota_recusa_mudar_capacidade_em_cronograma_em_analise(): void
    {
        $this->cronograma(['fase_tramite' => 'em_analise']);

        $sql = [];
        $this->esperaValidacao(function () use (&$sql): void {
            $sql = $this->sqlDe(fn () => app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['capacidade_m3' => 12.0])));
        }, 'capacidade_m3');

        $this->assertSame(8.0, (float) DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('capacidade_m3'));
    }

    public function test_r2_frota_trava_os_cronogramas_do_caminhao(): void
    {
        $this->cronograma(['fase_tramite' => 'aprovado']);

        $sql = $this->sqlDe(function (): void {
            try {
                app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['ativo' => false]));
            } catch (ValidationException) {
            }
        });

        $this->assertNotNull($this->indiceDaTrava($sql, 'tdap_cronogramas'));
        $this->assertTrue((bool) DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('ativo'));
    }

    public function test_r2_frota_recusa_trocar_prestador_em_cronograma_aprovado(): void
    {
        $this->cronograma(['fase_tramite' => 'aprovado']);

        $this->esperaValidacao(
            fn () => app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['prestador_id' => $this->novoPrestador()])),
            'prestador_id',
        );
    }

    public function test_r2_frota_permite_outros_campos_e_cronograma_em_elaboracao(): void
    {
        $this->cronograma(['fase_tramite' => 'em_analise']);
        app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['observacoes' => 'pneu novo']));
        $this->assertSame('pneu novo', DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('observacoes'));

        $this->cronograma(['fase_tramite' => 'elaboracao']);
        app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['capacidade_m3' => 12.0]));
        $this->assertSame(12.0, (float) DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('capacidade_m3'));
    }

    /** Aprova pela regra real (CronoViagemService::validar), como a CEDEC. */
    private function aprovar(int $viagemId): CronoViagem
    {
        config(['tdap.viagens.exige_confirmacao_municipio' => false]);
        $cedec = User::factory()->create();
        $cedec->assignRole(Role::where('slug', 'super-admin')->firstOrFail());

        return app(CronoViagemService::class)->validar($viagemId, true, null, $cedec->fresh());
    }

    private function entregaDaAlocacao(): array
    {
        $cc = DB::table('tdap_crono_caminhoes')->find($this->alocacaoId);

        return ['agua' => (float) $cc->agua_entregue, 'valor' => (float) $cc->vr_total];
    }

    public function test_n2_aprovacao_grava_capacidade_e_preco_na_viagem(): void
    {
        $this->emExecucao();

        $aprovada = $this->aprovar($this->viagem(null));

        $this->assertSame(8.0, (float) $aprovada->capacidade_m3);
        $this->assertSame(10.0, (float) $aprovada->valor_m3);
        $this->assertSame(['agua' => 8.0, 'valor' => 80.0], $this->entregaDaAlocacao());

        // Rejeitada nao grava nada.
        $rejeitada = app(CronoViagemService::class)->validar($this->viagem(null), false, 'nao houve', User::factory()->create(['orgao_principal_id' => \App\Modules\Compdec\Models\Orgao::factory()->cedec()->create()->id]));
        $this->assertNull($rejeitada->capacidade_m3);
    }

    public function test_n2_mudar_capacidade_e_preco_vale_so_para_as_proximas_aprovacoes(): void
    {
        $this->emExecucao();
        $this->aprovar($this->viagem(null));

        // Correcao de capacidade e reajuste de preco agora passam...
        app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['capacidade_m3' => 12.0]));
        app(LoteService::class)->atualizar($this->loteId, $this->dtoLote(15.0));
        $this->assertSame(12.0, (float) DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('capacidade_m3'));

        // ...e a proxima aprovacao recalcula a alocacao sem mexer na anterior:
        // 8 m3 x R$10 + 12 m3 x R$15.
        $this->aprovar($this->viagem(null));
        $this->assertSame(['agua' => 20.0, 'valor' => 260.0], $this->entregaDaAlocacao());

        // A correcao em massa usa a mesma conta e nao reescreve o passado.
        $cc = CronoCaminhao::query()->with(CronoCaminhaoService::relacoesDaEntrega())->findOrFail($this->alocacaoId);
        $this->assertSame(['agua_entregue' => 20.0, 'vr_total' => 260.0], app(CronoCaminhaoService::class)->entregaCalculada($cc));
    }

    public function test_n2_viagem_aprovada_sem_valor_gravado_usa_o_cadastro(): void
    {
        // Aprovada antes da coluna existir e fora do backfill.
        $this->viagem(CronoViagem::STATUS_APROVADA);
        $cc = CronoCaminhao::query()->with(CronoCaminhaoService::relacoesDaEntrega())->findOrFail($this->alocacaoId);

        $this->assertSame(['agua_entregue' => 8.0, 'vr_total' => 80.0], app(CronoCaminhaoService::class)->entregaCalculada($cc));
    }

    public function test_n2_frota_aceita_capacidade_com_viagem_so_pendente(): void
    {
        $this->emExecucao();
        $this->viagem(null);

        app(FrotaService::class)->atualizar($this->caminhaoId, $this->dtoCaminhao(['capacidade_m3' => 12.0]));

        $this->assertSame(12.0, (float) DB::table('tdap_caminhoes')->where('id', $this->caminhaoId)->value('capacidade_m3'));
    }

    private function dtoLote(float $valorM3, ?string $observacoes = null): LoteDTO
    {
        return new LoteDTO($this->ataId, [$this->municipioId], null, $this->prestadorId, 'L-RN', null, null, 1000.0, $valorM3, null, $observacoes);
    }

    public function test_n2_lote_sem_viagem_aprovada_muda_valor_m3(): void
    {
        app(LoteService::class)->atualizar($this->loteId, $this->dtoLote(15.0));

        $this->assertSame(15.0, (float) DB::table('tdap_lotes')->where('id', $this->loteId)->value('valor_m3'));
    }

    /* ---------------------------------------------------- N4 prorrogar */

    public function test_n4_prorrogacao_emenda_no_dia_seguinte_ao_fim_efetivo(): void
    {
        $this->emExecucao();
        $fim = Cronograma::findOrFail($this->cronogramaId)->dt_final->copy();
        $svc = app(CronogramaService::class);

        // Mesmo dia do fim (sobrepoe) e com lacuna: recusados.
        $this->esperaDominio(fn () => $svc->prorrogar($this->cronogramaId, $fim->toDateString(), $fim->copy()->addDays(30)->toDateString()), 'dia seguinte');
        $this->esperaDominio(fn () => $svc->prorrogar($this->cronogramaId, $fim->copy()->addDays(3)->toDateString(), $fim->copy()->addDays(30)->toDateString()), 'dia seguinte');

        $sql = $this->sqlDe(fn () => $svc->prorrogar($this->cronogramaId, $fim->copy()->addDay()->toDateString(), $fim->copy()->addDays(30)->toDateString()));
        $this->assertNotNull($this->indiceDaTrava($sql, 'tdap_cronogramas'));
        $this->assertTrue(Cronograma::find($this->cronogramaId)->dt_final_efetiva->equalTo($fim->copy()->addDays(30)));

        // Segunda prorrogacao: conta a partir do fim EFETIVO, nao do dt_final.
        $this->esperaDominio(fn () => $svc->prorrogar($this->cronogramaId, $fim->copy()->addDay()->toDateString(), $fim->copy()->addDays(20)->toDateString()), 'dia seguinte');

        // ...e ESTENDE a primeira: o inicio da 1a janela continua gravado, senao
        // as viagens dela ficariam fora da vigencia efetiva.
        $svc->prorrogar($this->cronogramaId, $fim->copy()->addDays(31)->toDateString(), $fim->copy()->addDays(60)->toDateString());
        $depois = Cronograma::findOrFail($this->cronogramaId);
        $this->assertTrue($depois->dt_inicio_prorrogacao->equalTo($fim->copy()->addDay()));
        $this->assertTrue($depois->dt_final_efetiva->equalTo($fim->copy()->addDays(60)));
    }

    public function test_ficha_nao_acusa_caminhao_da_propria_empresa_como_de_outra(): void
    {
        // obter() carrega caminhoes.caminhao sem prestador_id; a regra de
        // empresa nao pode depender dessa relacao parcial.
        $this->cronograma(['fase_tramite' => 'aprovado']);
        $svc = app(CronogramaService::class);

        [, $motivoFicha] = $svc->podeAtivar($svc->obter($this->cronogramaId));
        [, $motivoCru] = $svc->podeAtivar(Cronograma::findOrFail($this->cronogramaId));

        $this->assertStringNotContainsString('empresa contratada', (string) $motivoFicha);
        $this->assertSame($motivoCru, $motivoFicha);

        // E continua pegando o caminhao que e mesmo de outra empresa.
        DB::table('tdap_crono_caminhoes')->insert(['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $this->novoCaminhao($this->novoPrestador()), 'created_at' => now(), 'updated_at' => now()]);
        [, $motivo] = $svc->podeAtivar($svc->obter($this->cronogramaId));
        $this->assertStringContainsString('empresa contratada', (string) $motivo);
    }

    /* ----------------------------------------------- N6 vistoria */

    public function test_n6_editar_vistoria_que_sustenta_cronograma_ativo_nao_reduz_cobertura(): void
    {
        $this->emExecucao();
        $vistoriaId = (int) DB::table('tdap_vistorias')->where('placa_id', $this->caminhaoId)->value('id');
        $svc = app(VistoriaService::class);

        $this->esperaDominio(fn () => $svc->atualizar($vistoriaId, $this->dtoVistoria($vistoriaId, ['parecer' => 'reprovada'])), 'em execucao');
        $this->esperaDominio(fn () => $svc->atualizar($vistoriaId, $this->dtoVistoria($vistoriaId, ['data' => now()->subMonths(11)->toDateString()])), 'em execucao');
        $this->esperaDominio(fn () => $svc->atualizar($vistoriaId, $this->dtoVistoria($vistoriaId, ['placa_id' => $this->novoCaminhao()])), 'em execucao');

        $svc->atualizar($vistoriaId, $this->dtoVistoria($vistoriaId, ['observacoes' => 'ok', 'ficha' => 'F-1']));
        $this->assertSame('F-1', Vistoria::find($vistoriaId)->ficha);
        $this->assertSame('aprovada', Vistoria::find($vistoriaId)->parecer->value);
    }

    public function test_n6_vistoria_sem_cronograma_ativo_continua_editavel(): void
    {
        $vistoriaId = (int) DB::table('tdap_vistorias')->where('placa_id', $this->caminhaoId)->value('id');

        app(VistoriaService::class)->atualizar($vistoriaId, $this->dtoVistoria($vistoriaId, ['parecer' => 'reprovada']));

        $this->assertSame('reprovada', Vistoria::find($vistoriaId)->parecer->value);
    }

    /* ----------------------------------------------- N10 arquivar */

    public function test_n10_arquivar_recusa_cronograma_ativo(): void
    {
        $this->emExecucao();

        $this->esperaDominio(fn () => app(CronogramaService::class)->arquivar($this->cronogramaId), 'Encerre-o antes');
        $this->assertNull(Cronograma::find($this->cronogramaId)->arquivado_em);
    }

    public function test_n10_arquivar_e_desarquivar_travam_a_linha(): void
    {
        $svc = app(CronogramaService::class);

        $sql = $this->sqlDe(fn () => $svc->arquivar($this->cronogramaId));
        $this->assertNotNull($this->indiceDaTrava($sql, 'tdap_cronogramas'));
        $this->assertNotNull(Cronograma::find($this->cronogramaId)->arquivado_em);

        $sql = $this->sqlDe(fn () => $svc->desarquivar($this->cronogramaId));
        $this->assertNotNull($this->indiceDaTrava($sql, 'tdap_cronogramas'));
        $this->assertNull(Cronograma::find($this->cronogramaId)->arquivado_em);
    }

    public function test_n10_arquivar_encerrado_continua_permitido(): void
    {
        $this->cronograma(['ativo' => false, 'encerrado_em' => now()]);

        app(CronogramaService::class)->arquivar($this->cronogramaId);

        $this->assertNotNull(Cronograma::find($this->cronogramaId)->arquivado_em);
    }

    /* ------------------------------------------ N11 comprovante */

    private function comprovante(): CronogramaComprovante
    {
        Storage::fake('tdap');
        Storage::disk('tdap')->put('cronogramas/x/comprovantes/a.pdf', 'pdf');

        return CronogramaComprovante::create([
            'cronograma_id' => $this->cronogramaId, 'nome_original' => 'a.pdf', 'nome_arquivo' => 'a.pdf',
            'path' => 'cronogramas/x/comprovantes/a.pdf', 'disk' => 'tdap',
        ]);
    }

    public function test_n11_comprovante_de_cronograma_encerrado_nao_sai(): void
    {
        $comprovante = $this->comprovante();
        $this->cronograma(['ativo' => false, 'encerrado_em' => now()]);

        $this->esperaDominio(fn () => app(CronogramaComprovanteService::class)->destroy($comprovante), 'encerrado');
        $this->assertNotNull(CronogramaComprovante::find($comprovante->id));
        Storage::disk('tdap')->assertExists('cronogramas/x/comprovantes/a.pdf');
    }

    public function test_n11_comprovante_de_cronograma_em_execucao_sai(): void
    {
        $comprovante = $this->comprovante();
        $this->emExecucao();

        app(CronogramaComprovanteService::class)->destroy($comprovante);

        $this->assertNull(CronogramaComprovante::find($comprovante->id));
        Storage::disk('tdap')->assertMissing('cronogramas/x/comprovantes/a.pdf');
    }

    /* ------------------------------------------------ P3 fotos orfas */

    public function test_p3_foto_cuja_linha_falha_nao_fica_no_disco(): void
    {
        Storage::fake('tdap');
        $fantasma = new Vistoria();
        $fantasma->id = 2_000_000_000; // FK de tdap_vistoria_fotos recusa

        try {
            // Transacao aninhada = savepoint: o erro do Postgres nao aborta a do teste.
            DB::transaction(fn () => app(VistoriaFotoService::class)->store($fantasma, UploadedFile::fake()->image('a.jpg')));
            $this->fail('esperava falha ao gravar a linha');
        } catch (\Illuminate\Database\QueryException) {
        }

        $this->assertSame([], Storage::disk('tdap')->allFiles());
    }

    public function test_p3_rollback_da_vistoria_apaga_as_fotos_ja_gravadas(): void
    {
        Storage::fake('tdap');
        $vistoriaId = (int) DB::table('tdap_vistorias')->where('placa_id', $this->caminhaoId)->value('id');
        $svc = app(VistoriaFotoService::class);
        $gravadas = [];

        try {
            DB::transaction(function () use ($svc, $vistoriaId, &$gravadas): void {
                $gravadas[] = $svc->store(Vistoria::findOrFail($vistoriaId), UploadedFile::fake()->image('a.jpg'));
                $gravadas[] = $svc->store(Vistoria::findOrFail($vistoriaId), UploadedFile::fake()->image('b.jpg'));
                throw new \RuntimeException('falha na terceira foto');
            });
        } catch (\RuntimeException) {
            $svc->descartarArquivos($gravadas);
        }

        $this->assertCount(2, $gravadas);
        $this->assertSame([], Storage::disk('tdap')->allFiles());
        $this->assertSame(0, VistoriaFoto::where('vistoria_id', $vistoriaId)->count());
    }

    public function test_p3_controller_apaga_fotos_quando_a_gravacao_falha(): void
    {
        Storage::fake('tdap');
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $user = User::factory()->create();
        $user->assignRole(Role::where('slug', 'super-admin')->firstOrFail());
        $user = $user->fresh();
        $this->actingAs($user)->withSession([\App\Services\Auth\SessionVersionService::SESSION_KEY => $user->session_version]);

        // Grava a primeira foto de verdade e falha na segunda.
        $this->app->instance(VistoriaFotoService::class, new class extends VistoriaFotoService {
            private int $chamadas = 0;

            public function store(Vistoria $vistoria, UploadedFile $file, ?string $descricao = null): VistoriaFoto
            {
                if (++$this->chamadas === 2) {
                    throw new \RuntimeException('disco cheio');
                }

                return parent::store($vistoria, $file, $descricao);
            }
        });
        $antes = Vistoria::where('placa_id', $this->caminhaoId)->count();

        $resposta = $this->post(route('tdap.frota.vistorias.store', $this->caminhaoId), [
            'nome' => 'Vistoriador', 'data' => now()->toDateString(), 'capacidade' => 8, 'parecer' => 'reprovada',
            'fotos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]);

        $resposta->assertStatus(500);
        $this->assertSame([], Storage::disk('tdap')->allFiles());
        $this->assertSame($antes, Vistoria::where('placa_id', $this->caminhaoId)->count());
    }

    /* --------------------------------------- comunidade da alocacao */

    private function comunidade(int $municipioId): int
    {
        return DB::table('comunidades')->insertGetId(['municipio_id' => $municipioId, 'nome' => 'Comunidade '.random_int(1, 99999), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function validarAlocacao(\Illuminate\Foundation\Http\FormRequest $request, array $dados): \Illuminate\Validation\Validator
    {
        // Sem metodo definido o request e GET, e input() le a query string.
        $request->initialize($dados);
        $request->setContainer($this->app);
        $validator = Validator::make($dados, $request->rules());
        $request->withValidator($validator);
        $validator->passes();

        return $validator;
    }

    public function test_comunidade_store_exige_existente_e_do_municipio(): void
    {
        $base = ['cronograma_id' => $this->cronogramaId, 'caminhao_id' => $this->novoCaminhao(), 'agua_prevista' => 10, 'num_viagens' => 2];

        $inexistente = $this->validarAlocacao(new StoreCronoCaminhaoRequest(), $base + ['comunidade_id' => 2_000_000_000]);
        $this->assertSame('A comunidade selecionada nao existe.', $inexistente->errors()->first('comunidade_id'));

        $outroMunicipio = $this->comunidade(Municipio::factory()->create()->id);
        $errada = $this->validarAlocacao(new StoreCronoCaminhaoRequest(), $base + ['comunidade_id' => $outroMunicipio]);
        $this->assertSame('A comunidade nao pertence ao Municipio do cronograma.', $errada->errors()->first('comunidade_id'));

        $certa = $this->validarAlocacao(new StoreCronoCaminhaoRequest(), $base + ['comunidade_id' => $this->comunidade($this->municipioId)]);
        $this->assertFalse($certa->errors()->has('comunidade_id'));

        $sem = $this->validarAlocacao(new StoreCronoCaminhaoRequest(), $base + ['comunidade_id' => null]);
        $this->assertFalse($sem->errors()->has('comunidade_id'));
    }

    public function test_comunidade_update_mantem_id_legado_e_recusa_troca_invalida(): void
    {
        DB::table('tdap_crono_caminhoes')->where('id', $this->alocacaoId)->update(['comunidade_id' => 1_999_999_999]);
        $request = new UpdateCronoCaminhaoRequest();
        $route = new Route('PUT', 'crono-caminhoes/{cronoCaminhao}', []);
        $route->bind($request);
        $route->setParameter('cronoCaminhao', $this->alocacaoId);
        $request->setRouteResolver(fn () => $route);
        $base = ['agua_prevista' => 10, 'num_viagens' => 2];

        $mantem = $this->validarAlocacao($request, $base + ['comunidade_id' => 1_999_999_999]);
        $this->assertFalse($mantem->errors()->has('comunidade_id'));

        $troca = $this->validarAlocacao($request, $base + ['comunidade_id' => 2_000_000_000]);
        $this->assertTrue($troca->errors()->has('comunidade_id'));
    }
}
