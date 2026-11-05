<?php

declare(strict_types=1);

namespace Tests\Unit\Tdap;

use App\Modules\Tdap\Support\ExportadorCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExportadorCsvNeutralizaFormulaTest extends TestCase
{
    /** @return array<string, array{0: mixed, 1: mixed}> */
    public static function casos(): array
    {
        return [
            'igual'           => ['=1+1', "'=1+1"],
            'mais'            => ['+A1', "'+A1"],
            'menos texto'     => ['-A1+B2', "'-A1+B2"],
            'arroba'          => ['@SOMA(A1)', "'@SOMA(A1)"],
            'tab'             => ["\t=1", "'\t=1"],
            'cr'              => ["\r=1", "'\r=1"],
            'texto comum'     => ['Prestador X', 'Prestador X'],
            'vazio'           => ['', ''],
            'nulo'            => [null, null],
            'inteiro'         => [-5, -5],
            'float'           => [-1.5, -1.5],
            'numero negativo' => ['-1.234,50', '-1.234,50'],
            'numero positivo' => ['+10', '+10'],
            'igual no meio'   => ['a=b', 'a=b'],
        ];
    }

    #[DataProvider('casos')]
    public function test_neutraliza_so_celula_que_vira_formula(mixed $entrada, mixed $esperado): void
    {
        $this->assertSame($esperado, ExportadorCsv::neutralizar($entrada));
    }

    public function test_neutraliza_linha_inteira(): void
    {
        $this->assertSame(["'=x", 'ok', 3], ExportadorCsv::neutralizarLinha(['=x', 'ok', 3]));
    }
}
