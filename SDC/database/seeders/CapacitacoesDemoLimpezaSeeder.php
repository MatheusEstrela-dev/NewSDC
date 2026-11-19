<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Remove SO os dados criados pelo CapacitacoesDemoSeeder (cursos "[DEMO] ", contas
 * @demo.test e o que depende deles). Nao entra no DatabaseSeeder.
 *
 *   php artisan db:seed --class=CapacitacoesDemoLimpezaSeeder
 */
final class CapacitacoesDemoLimpezaSeeder extends Seeder
{
    public function run(): void
    {
        $antes = CapacitacoesDemoSeeder::limpar();

        $this->command?->info(sprintf(
            'Dados demo removidos: %d cursos, %d inscricoes, %d certificados, %d cidadaos, %d servidores.',
            $antes['cursos'], $antes['inscricoes'], array_sum($antes['certificados_por_status']), $antes['cidadaos'], $antes['servidores'],
        ));
    }
}
