<?php

namespace Database\Seeders;

use App\Modules\Decretacoes\Enums\Redec;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula a tabela rat_redec com as 19 Regiões de Defesa Civil (REDEC) de Minas Gerais.
 *
 * A lista é a mesma do enum App\Modules\Decretacoes\Enums\Redec — que é a fonte
 * única de verdade — para que a tabela de referência e as listas suspensas do
 * módulo de Decretações nunca divirjam.
 *
 * Esta é uma tabela de referência imutável. O seeder é idempotente:
 * usa updateOrInsert na sigla, portanto pode ser reexecutado sem duplicar.
 */
class RatRedecSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach (Redec::cases() as $redec) {
            DB::table('rat_redec')->updateOrInsert(
                ['sigla' => $redec->sigla()],
                [
                    'nome'       => 'Região de Defesa Civil de ' . $redec->sede() . ' (' . $redec->rpm() . ')',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('RatRedecSeeder: ' . count(Redec::cases()) . ' REDECs de Minas Gerais inseridas.');
    }
}
