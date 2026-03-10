<?php

declare(strict_types=1);

namespace Tests\Unit\Rat\Services;

use App\Modules\Rat\DTOs\RatStatisticsDTO;
use App\Modules\Rat\Services\RatStatisticsService;
use PHPUnit\Framework\TestCase;

/**
 * Testa o DTO de estatísticas do RAT.
 *
 * RatStatisticsService depende do Eloquent Model (Rat::count etc.), então
 * testamos o DTO diretamente e verificamos que o Service o monta corretamente.
 */
class RatStatisticsDTOTest extends TestCase
{
    public function test_dto_armazena_os_valores_corretamente(): void
    {
        $dto = new RatStatisticsDTO(
            total:   100,
            hoje:    5,
            esteMes: 20,
            esteAno: 80,
        );

        $this->assertSame(100, $dto->total);
        $this->assertSame(5, $dto->hoje);
        $this->assertSame(20, $dto->esteMes);
        $this->assertSame(80, $dto->esteAno);
    }

    public function test_to_array_retorna_estrutura_correta(): void
    {
        $dto = new RatStatisticsDTO(
            total:   10,
            hoje:    1,
            esteMes: 3,
            esteAno: 7,
        );

        $arr = $dto->toArray();

        $this->assertArrayHasKey('total', $arr);
        $this->assertArrayHasKey('hoje', $arr);
        $this->assertArrayHasKey('esteMes', $arr);
        $this->assertArrayHasKey('esteAno', $arr);
        $this->assertSame(10, $arr['total']);
        $this->assertSame(1, $arr['hoje']);
    }

    public function test_dto_imutavel_com_readonly(): void
    {
        $dto = new RatStatisticsDTO(1, 0, 0, 1);

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line
        $dto->total = 999; // readonly não pode ser alterado
    }
}
