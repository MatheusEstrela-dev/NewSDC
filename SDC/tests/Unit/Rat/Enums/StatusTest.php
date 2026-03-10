<?php

declare(strict_types=1);

namespace Tests\Unit\Rat\Enums;

use App\Modules\Rat\Enums\Status;
use PHPUnit\Framework\TestCase;

/**
 * Testa o enum Status do módulo RAT.
 *
 * Garante que cada case possua label, cor e que tryFrom funciona corretamente.
 */
class StatusTest extends TestCase
{
    public function test_todos_os_cases_existem(): void
    {
        $cases = Status::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(Status::RASCUNHO, $cases);
        $this->assertContains(Status::EM_ANDAMENTO, $cases);
        $this->assertContains(Status::FINALIZADO, $cases);
        $this->assertContains(Status::ARQUIVADO, $cases);
    }

    public function test_labels_corretos(): void
    {
        $this->assertSame('Rascunho', Status::RASCUNHO->getLabel());
        $this->assertSame('Em Andamento', Status::EM_ANDAMENTO->getLabel());
        $this->assertSame('Finalizado', Status::FINALIZADO->getLabel());
        $this->assertSame('Arquivado', Status::ARQUIVADO->getLabel());
    }

    public function test_cores_corretas(): void
    {
        $this->assertSame('warning', Status::RASCUNHO->getColorClass());
        $this->assertSame('info', Status::EM_ANDAMENTO->getColorClass());
        $this->assertSame('success', Status::FINALIZADO->getColorClass());
        $this->assertSame('secondary', Status::ARQUIVADO->getColorClass());
    }

    public function test_try_from_valores_validos(): void
    {
        $this->assertSame(Status::RASCUNHO, Status::tryFrom('rascunho'));
        $this->assertSame(Status::EM_ANDAMENTO, Status::tryFrom('em_andamento'));
        $this->assertSame(Status::FINALIZADO, Status::tryFrom('finalizado'));
        $this->assertSame(Status::ARQUIVADO, Status::tryFrom('arquivado'));
    }

    public function test_try_from_valor_invalido_retorna_null(): void
    {
        $this->assertNull(Status::tryFrom('inexistente'));
        $this->assertNull(Status::tryFrom(''));
        $this->assertNull(Status::tryFrom('RASCUNHO')); // case-sensitive
    }

    public function test_to_select_array_contem_todos_os_valores(): void
    {
        $arr = Status::toSelectArray();

        $this->assertArrayHasKey('rascunho', $arr);
        $this->assertArrayHasKey('em_andamento', $arr);
        $this->assertArrayHasKey('finalizado', $arr);
        $this->assertArrayHasKey('arquivado', $arr);
        $this->assertSame('Rascunho', $arr['rascunho']);
        $this->assertSame('Em Andamento', $arr['em_andamento']);
    }
}
