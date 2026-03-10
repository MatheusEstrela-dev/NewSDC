<?php

declare(strict_types=1);

namespace Tests\Unit\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Services\RatProtocoloService;
use PHPUnit\Framework\TestCase;

/**
 * Testa a geração de protocolo sequencial do RatProtocoloService.
 *
 * Isolado com mock do repositório — sem acesso ao banco de dados.
 */
class RatProtocoloServiceTest extends TestCase
{
    private function makeService(int $latestSequence): RatProtocoloService
    {
        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->method('getLatestSequence')->willReturn($latestSequence);

        return new RatProtocoloService($repo);
    }

    public function test_formato_do_protocolo_gerado(): void
    {
        $service  = $this->makeService(0);
        $protocolo = $service->generate();
        $year      = date('Y');

        $this->assertMatchesRegularExpression(
            '/^RAT-\d{4}-\d{5}$/',
            $protocolo,
            'Protocolo deve seguir o padrão RAT-AAAA-NNNNN'
        );
        $this->assertStringStartsWith("RAT-{$year}-", $protocolo);
    }

    public function test_primeiro_protocolo_do_ano_e_00001(): void
    {
        $service   = $this->makeService(0); // nenhum no banco
        $protocolo = $service->generate();
        $year      = date('Y');

        $this->assertSame("RAT-{$year}-00001", $protocolo);
    }

    public function test_sequencia_incrementa_a_partir_do_ultimo(): void
    {
        $service   = $this->makeService(42); // último seq foi 42
        $protocolo = $service->generate();
        $year      = date('Y');

        $this->assertSame("RAT-{$year}-00043", $protocolo);
    }

    public function test_zeroes_sao_preenchidos_ate_5_digitos(): void
    {
        $service   = $this->makeService(9999); // próximo será 10000
        $protocolo = $service->generate();
        $year      = date('Y');

        $this->assertSame("RAT-{$year}-10000", $protocolo);
    }

    public function test_repositorio_e_chamado_com_ano_atual(): void
    {
        $repo = $this->createMock(RatRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('getLatestSequence')
            ->with((int) date('Y'))
            ->willReturn(0);

        $service = new RatProtocoloService($repo);
        $service->generate();
    }
}
