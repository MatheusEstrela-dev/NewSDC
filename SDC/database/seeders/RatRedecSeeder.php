<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula a tabela rat_redec com as 14 Regiões de Defesa Civil (REDEC) de Minas Gerais.
 *
 * Esta é uma tabela de referência imutável. O seeder é idempotente:
 * usa updateOrInsert na sigla, portanto pode ser reexecutado sem duplicar.
 */
class RatRedecSeeder extends Seeder
{
    /** @var array<int, array{nome: string, sigla: string}> */
    private const REDECS = [
        ['sigla' => '1ª REDEC', 'nome' => 'Primeira Região de Defesa Civil — Metropolitana de Belo Horizonte'],
        ['sigla' => '2ª REDEC', 'nome' => 'Segunda Região de Defesa Civil — Vale do Paraopeba'],
        ['sigla' => '3ª REDEC', 'nome' => 'Terceira Região de Defesa Civil — Campo das Vertentes'],
        ['sigla' => '4ª REDEC', 'nome' => 'Quarta Região de Defesa Civil — Zona da Mata'],
        ['sigla' => '5ª REDEC', 'nome' => 'Quinta Região de Defesa Civil — Triângulo Norte'],
        ['sigla' => '6ª REDEC', 'nome' => 'Sexta Região de Defesa Civil — Triângulo Sul'],
        ['sigla' => '7ª REDEC', 'nome' => 'Sétima Região de Defesa Civil — Norte de Minas'],
        ['sigla' => '8ª REDEC', 'nome' => 'Oitava Região de Defesa Civil — Vale do Rio Doce'],
        ['sigla' => '9ª REDEC', 'nome' => 'Nona Região de Defesa Civil — Mucuri'],
        ['sigla' => '10ª REDEC', 'nome' => 'Décima Região de Defesa Civil — Oeste de Minas'],
        ['sigla' => '11ª REDEC', 'nome' => 'Décima Primeira Região de Defesa Civil — Sul de Minas'],
        ['sigla' => '12ª REDEC', 'nome' => 'Décima Segunda Região de Defesa Civil — Circuito das Águas'],
        ['sigla' => '13ª REDEC', 'nome' => 'Décima Terceira Região de Defesa Civil — Serrana do Sul'],
        ['sigla' => '14ª REDEC', 'nome' => 'Décima Quarta Região de Defesa Civil — Jequitinhonha'],
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::REDECS as $redec) {
            DB::table('rat_redec')->updateOrInsert(
                ['sigla' => $redec['sigla']],
                [
                    'nome'       => $redec['nome'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('RatRedecSeeder: ' . count(self::REDECS) . ' REDECs de Minas Gerais inseridas.');
    }
}
