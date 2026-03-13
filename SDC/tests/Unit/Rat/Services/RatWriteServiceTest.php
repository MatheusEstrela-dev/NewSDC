<?php

declare(strict_types=1);

namespace Tests\Unit\Rat\Services;

use App\Modules\Rat\DTOs\CreateRatDTO;
use App\Modules\Rat\DTOs\UpdateRatDTO;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatWriteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Testa RatWriteService com banco de dados real.
 *
 * Verifica que create(), createWithData(), update(), saveDraft() e finalize()
 * persistem os dados e aplicam as regras de negócio corretamente.
 */
class RatWriteServiceTest extends TestCase
{
    use DatabaseTransactions;

    private RatWriteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RatWriteService::class);
    }

    private function makeRat(string $status = 'rascunho'): Rat
    {
        return Rat::create([
            'protocolo'    => 'RAT-TEST-' . uniqid(),
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

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_persiste_rat_em_branco(): void
    {
        $rat = $this->service->create();

        $this->assertNotNull($rat->id);
        $this->assertSame(Status::RASCUNHO->value, $rat->status);
        $this->assertStringStartsWith('RAT-', $rat->protocolo);
        $this->assertDatabaseHas('rats', ['id' => $rat->id]);
    }

    // -------------------------------------------------------------------------
    // createWithData()
    // -------------------------------------------------------------------------

    public function test_create_with_data_persiste_dados_do_dto(): void
    {
        $dto = CreateRatDTO::from([
            'dadosGerais' => ['data_fato' => '2026-03-01'],
            'comunicacao' => ['tipo_solicitacao' => 'telefone'],
        ]);

        $rat = $this->service->createWithData($dto);

        $this->assertNotNull($rat->id);
        $this->assertSame(Status::RASCUNHO->value, $rat->status);
        $this->assertSame('2026-03-01', $rat->dados_gerais['data_fato'] ?? null);
    }

    public function test_create_with_data_sem_dados_cria_rascunho_em_branco(): void
    {
        $dto = CreateRatDTO::from([]);
        $rat = $this->service->createWithData($dto);

        $this->assertNotNull($rat->id);
        $this->assertSame(Status::RASCUNHO->value, $rat->status);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_muda_status_para_em_andamento(): void
    {
        $rat = $this->makeRat(Status::RASCUNHO->value);
        $dto = UpdateRatDTO::from(['dadosGerais' => ['data_fato' => '2026-01-01']]);

        $result = $this->service->update($rat->id, $dto);

        $this->assertSame(Status::EM_ANDAMENTO->value, $result->status);
    }

    public function test_update_persiste_dados_gerais(): void
    {
        $rat = $this->makeRat();
        $dto = UpdateRatDTO::from([
            'dadosGerais' => ['data_fato' => '2026-03-01'],
            'comunicacao' => ['tipo_solicitacao' => 'radio'],
        ]);

        $result = $this->service->update($rat->id, $dto);

        $this->assertSame('2026-03-01', $result->dados_gerais['data_fato'] ?? null);
        $this->assertSame('radio', $result->comunicacao['tipo_solicitacao'] ?? null);
    }

    // -------------------------------------------------------------------------
    // saveDraft()
    // -------------------------------------------------------------------------

    public function test_save_draft_mantem_status_rascunho(): void
    {
        $rat = $this->makeRat(Status::RASCUNHO->value);
        $dto = UpdateRatDTO::from(['dadosGerais' => ['data_fato' => '2026-02-01']]);

        $result = $this->service->saveDraft($rat->id, $dto);

        $this->assertSame(Status::RASCUNHO->value, $result->status);
    }

    public function test_save_draft_persiste_dados(): void
    {
        $rat = $this->makeRat();
        $dto = UpdateRatDTO::from(['comunicacao' => ['nome_solicitante' => 'João']]);

        $result = $this->service->saveDraft($rat->id, $dto);

        $this->assertSame('João', $result->comunicacao['nome_solicitante'] ?? null);
    }

    // -------------------------------------------------------------------------
    // finalize()
    // -------------------------------------------------------------------------

    public function test_finalize_muda_status_para_finalizado(): void
    {
        $rat    = $this->makeRat(Status::EM_ANDAMENTO->value);
        $result = $this->service->finalize($rat->id);

        $this->assertSame(Status::FINALIZADO->value, $result->status);
    }

    public function test_finalize_lanca_422_quando_ja_finalizado(): void
    {
        $rat = $this->makeRat(Status::FINALIZADO->value);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->finalize($rat->id);
    }

    public function test_finalize_lanca_404_quando_rat_nao_existe(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->finalize('id-inexistente');
    }
}
