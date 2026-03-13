<?php

declare(strict_types=1);

namespace Tests\Unit\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatProtocoloService;
use App\Modules\Rat\Services\RatWriteService;
use PHPUnit\Framework\TestCase;

/**
 * Testa RatWriteService com repositório mockado.
 *
 * Verifica que create(), update(), saveDraft() e finalize()
 * delegam ao repositório com os parâmetros corretos.
 */
class RatWriteServiceTest extends TestCase
{
    private function makeRat(string $status = 'rascunho'): Rat
    {
        /** @var Rat&\PHPUnit\Framework\MockObject\MockObject $rat */
        $rat = $this->getMockBuilder(Rat::class)
            ->onlyMethods(['fresh'])
            ->getMock();

        // Usa Eloquent real para que __get/setAttribute funcionem corretamente
        $rat->forceFill(['id' => 'uuid-test-123', 'status' => $status]);
        $rat->method('fresh')->willReturn($rat);

        return $rat;
    }

    private function makeService(
        ?RatRepositoryInterface $repo = null,
        ?RatProtocoloService $protocoloService = null,
    ): RatWriteService {
        $repo              ??= $this->createMock(RatRepositoryInterface::class);
        $protocoloService  ??= $this->createMock(RatProtocoloService::class);

        return new RatWriteService($repo, $protocoloService);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_injeta_status_em_andamento(): void
    {
        $rat  = $this->makeRat();
        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('update')
            ->with(
                'uuid-test-123',
                $this->callback(fn(array $data) =>
                    ($data['status'] ?? null) === Status::EM_ANDAMENTO->value
                )
            )
            ->willReturn($rat);

        $svc = $this->makeService($repo);
        $result = $svc->update('uuid-test-123', ['dadosGerais' => ['data_fato' => '2026-01-01']]);

        $this->assertSame($rat, $result);
    }

    public function test_update_preserva_dados_passados(): void
    {
        $rat     = $this->makeRat();
        $payload = ['dadosGerais' => ['data_fato' => '2026-03-01'], 'comunicacao' => ['tipo_solicitacao' => 'telefone']];

        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('update')
            ->with('uuid-test-123', $this->callback(fn(array $data) =>
                isset($data['dadosGerais']) && isset($data['comunicacao'])
            ))
            ->willReturn($rat);

        $svc = $this->makeService($repo);
        $svc->update('uuid-test-123', $payload);
    }

    // -------------------------------------------------------------------------
    // saveDraft()
    // -------------------------------------------------------------------------

    public function test_save_draft_injeta_status_rascunho(): void
    {
        $rat  = $this->makeRat();
        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('update')
            ->with(
                'uuid-test-123',
                $this->callback(fn(array $data) =>
                    ($data['status'] ?? null) === Status::RASCUNHO->value
                )
            )
            ->willReturn($rat);

        $svc = $this->makeService($repo);
        $svc->saveDraft('uuid-test-123', []);
    }

    // -------------------------------------------------------------------------
    // finalize()
    // -------------------------------------------------------------------------

    public function test_finalize_retorna_404_quando_rat_nao_existe(): void
    {
        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $svc = $this->makeService($repo);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->finalize('nao-existe');
    }

    public function test_finalize_retorna_422_quando_ja_finalizado(): void
    {
        $rat = $this->makeRat(Status::FINALIZADO->value);

        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->method('findById')->willReturn($rat);

        $svc = $this->makeService($repo);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->finalize('uuid-test-123');
    }

    public function test_finalize_chama_update_status_para_finalizado(): void
    {
        $rat = $this->makeRat(Status::EM_ANDAMENTO->value);

        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->method('findById')->willReturn($rat);
        $repo->expects($this->once())
            ->method('updateStatus')
            ->with('uuid-test-123', Status::FINALIZADO->value);

        $svc = $this->makeService($repo);
        $result = $svc->finalize('uuid-test-123');

        $this->assertSame($rat, $result);
    }
}
