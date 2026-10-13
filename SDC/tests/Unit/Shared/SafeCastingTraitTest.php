<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Conversor compartilhado dos DTOs (#65). Os casos cobrem os defeitos do
 * docs/plans/plano_dtos.md: "false" virando true, UUID truncado para inteiro
 * e texto invalido virando zero.
 */
class SafeCastingTraitTest extends TestCase
{
    /** Expoe os metodos protegidos do trait para o teste. */
    private function caster(): object
    {
        return new class
        {
            use SafeCastingTrait;

            public function __call(string $metodo, array $args): mixed
            {
                return self::$metodo(...$args);
            }
        };
    }

    public static function booleanos(): array
    {
        return [
            'true'          => [true, true],
            'false'         => [false, false],
            'string false'  => ['false', false],
            'string FALSE'  => ['FALSE', false],
            'string 0'      => ['0', false],
            'int 0'         => [0, false],
            'int 1'         => [1, true],
            'string 1'      => ['1', true],
            'on'            => ['on', true],
            'off'           => ['off', false],
            'sim'           => ['sim', true],
            'nao'           => ['não', false],
            'vazio'         => ['', null],
            'null'          => [null, null],
            'int 2'         => [2, null],
            'texto'         => ['talvez', null],
            'array'         => [[1], null],
        ];
    }

    #[DataProvider('booleanos')]
    public function test_as_bool_or_null_distingue_falso_de_ausente(mixed $entrada, ?bool $esperado): void
    {
        $this->assertSame($esperado, $this->caster()->asBoolOrNull($entrada));
    }

    public function test_as_bool_usa_default_so_quando_ausente_ou_invalido(): void
    {
        $c = $this->caster();

        $this->assertFalse($c->asBool('false', true));
        $this->assertTrue($c->asBool(null, true));
        $this->assertFalse($c->asBool('abc'));
    }

    public static function inteiros(): array
    {
        return [
            'int'               => [42, 42],
            'string'            => ['42', 42],
            'negativo'          => ['-7', -7],
            'espacos'           => [' 12 ', 12],
            'float inteiro'     => [3.0, 3],
            'float fracionario' => [3.5, null],
            'string decimal'    => ['1.5', null],
            'lixo no fim'       => ['12abc', null],
            'texto'             => ['abc', null],
            'vazio'             => ['', null],
            'null'              => [null, null],
            'bool'              => [true, null],
            'uuid'              => ['0199a3b2-0000-7000-8000-000000000001', null],
        ];
    }

    #[DataProvider('inteiros')]
    public function test_as_int_or_null_nao_trunca_nem_inventa_zero(mixed $entrada, ?int $esperado): void
    {
        $this->assertSame($esperado, $this->caster()->asIntOrNull($entrada));
    }

    public static function decimais(): array
    {
        return [
            'int'             => [5, 5.0],
            'float'           => [-19.92, -19.92],
            'string ponto'    => ['-43.94', -43.94],
            'virgula'         => ['1,5', 1.5],
            'milhar pt-BR'    => ['1.234,56', 1234.56],
            'zero legitimo'   => ['0', 0.0],
            'texto'           => ['n/d', null],
            'vazio'           => ['', null],
            'null'            => [null, null],
            'nan'             => [NAN, null],
            'infinito'        => [INF, null],
        ];
    }

    #[DataProvider('decimais')]
    public function test_as_float_or_null_preserva_zero_e_recusa_texto(mixed $entrada, ?float $esperado): void
    {
        $this->assertSame($esperado, $this->caster()->asFloatOrNull($entrada));
    }

    public function test_as_float_e_as_int_aplicam_default_explicito(): void
    {
        $c = $this->caster();

        $this->assertSame(0.0, $c->asFloat('n/d'));
        $this->assertSame(9.5, $c->asFloat(null, 9.5));
        $this->assertSame(0.0, $c->asFloat('0', 9.5));
        $this->assertSame(7, $c->asInt('x', 7));
    }

    public function test_uuid_continua_string_e_preserva_zero_a_esquerda(): void
    {
        $c = $this->caster();

        $this->assertSame('0199a3b2-0000-7000-8000-000000000001', $c->asUuidOrNull('0199A3B2-0000-7000-8000-000000000001'));
        $this->assertNull($c->asUuidOrNull('199'));
        $this->assertNull($c->asUuidOrNull(199));
        $this->assertNull($c->asUuidOrNull(null));
    }

    public function test_strings(): void
    {
        $c = $this->caster();

        $this->assertSame('abc', $c->asString('abc'));
        $this->assertSame('12', $c->asString(12));
        $this->assertSame('padrao', $c->asString(null, 'padrao'));
        $this->assertSame('padrao', $c->asString([], 'padrao'));
        $this->assertNull($c->asStringOrNull(''));
        $this->assertNull($c->asStringOrNull(false));
        $this->assertSame(' com espaco ', $c->asStringOrNull(' com espaco '));
    }

    public function test_as_array(): void
    {
        $c = $this->caster();

        $this->assertSame(['a' => 1], $c->asArray(['a' => 1]));
        $this->assertSame([], $c->asArray('x'));
        $this->assertSame([], $c->asArray(null));
    }
}
