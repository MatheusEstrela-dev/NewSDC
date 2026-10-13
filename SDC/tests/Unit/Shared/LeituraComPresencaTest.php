<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Modules\Shared\DTOs\Traits\LeituraComPresenca;
use PHPUnit\Framework\TestCase;

/**
 * Contrato de update parcial (#67): ausente fica de fora, null/'' limpa,
 * false/0 gravam. E o que impede o update de apagar ou reescrever colunas que
 * o usuario nao enviou.
 */
class LeituraComPresencaTest extends TestCase
{
    private function leitor(): object
    {
        return new class
        {
            use LeituraComPresenca;

            public function __call(string $metodo, array $args): mixed
            {
                return match ($metodo) {
                    'ler' => self::ler($args[0], $args[1], ...array_slice($args, 2)),
                    default => self::$metodo(...$args),
                };
            }

            /** ler() recebe $presentes por referencia; expoe o acumulado. */
            public function lerTudo(array $dados, array $colunas): array
            {
                $presentes = [];
                $valores = [];
                foreach ($colunas as $coluna => $alternativas) {
                    $valores[$coluna] = self::ler($dados, $presentes, $coluna, ...$alternativas);
                }

                return [$valores, $presentes];
            }
        };
    }

    public function test_registra_so_as_colunas_que_vieram(): void
    {
        [, $presentes] = $this->leitor()->lerTudo(
            ['nome' => 'Ana', 'bairro' => null],
            ['nome' => [], 'bairro' => [], 'cep' => []],
        );

        $this->assertSame(['nome', 'bairro'], $presentes, 'null presente conta; ausente nao');
    }

    public function test_caminho_aninhado_e_alternativas_marcam_a_coluna_principal(): void
    {
        [$valores, $presentes] = $this->leitor()->lerTudo(
            ['solicitante' => ['nome' => 'Joao'], 'patologias' => ['trincas' => false]],
            ['v_solicitante_nome' => ['solicitante.nome'], 'v_patologia_trincas' => ['patologias.trincas']],
        );

        $this->assertSame('Joao', $valores['v_solicitante_nome']);
        $this->assertFalse($valores['v_patologia_trincas']);
        $this->assertSame(['v_solicitante_nome', 'v_patologia_trincas'], $presentes);
    }

    public function test_primeiro_caminho_existente_vence_mesmo_com_null(): void
    {
        [$valores] = $this->leitor()->lerTudo(
            ['data_fato' => null, 'data' => '2026-01-01'],
            ['data_fato' => ['data']],
        );

        $this->assertNull($valores['data_fato'], 'a chave principal enviada vazia limpa; o alias nao a substitui');
    }

    public function test_somente_presentes_filtra_e_converte_vazio_em_null(): void
    {
        $saida = $this->leitor()->somentePresentes(
            ['nome' => '', 'ativo' => false, 'qtd' => 0, 'obs' => null, 'cep' => '30000000'],
            ['nome', 'ativo', 'qtd', 'obs'],
        );

        $this->assertSame(['nome' => null, 'ativo' => false, 'qtd' => 0, 'obs' => null], $saida);
    }
}
