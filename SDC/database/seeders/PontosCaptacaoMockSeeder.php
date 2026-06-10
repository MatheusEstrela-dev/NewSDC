<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Tdap\Models\PontoCaptacao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de DEV para pontos de captacao (pip_pmda_ponto).
 *
 * Cria pontos para os municipios que ja possuem lote TDAP, garantindo que o
 * select de Ponto de Captacao do cronograma tenha opcoes em ambiente local
 * sem depender do banco legado. Idempotente por (municipio_id, nome).
 */
class PontosCaptacaoMockSeeder extends Seeder
{
    public function run(): void
    {
        $municipioIds = DB::table('tdap_lotes')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('municipio_id');

        if ($municipioIds->isEmpty()) {
            $this->command?->warn('Nenhum lote TDAP encontrado; nada a semear.');

            return;
        }

        foreach ($municipioIds as $municipioId) {
            $modelos = [
                ['nome' => 'ETA COPASA - SEDE',        'tipo' => 1, 'capacidade' => 1000],
                ['nome' => 'BARRAGEM MUNICIPAL',        'tipo' => 3, 'capacidade' => 5000],
                ['nome' => 'POCO ARTESIANO PUBLICO 01', 'tipo' => 5, 'capacidade' => 300],
            ];

            foreach ($modelos as $m) {
                PontoCaptacao::updateOrCreate(
                    ['municipio_id' => (int) $municipioId, 'nome' => $m['nome']],
                    [
                        'tipo'       => $m['tipo'],
                        'capacidade' => $m['capacidade'],
                        'ativo'      => true,
                    ],
                );
            }
        }

        $this->command?->info('Pontos de captacao (mock) semeados para '.$municipioIds->count().' municipio(s).');
    }
}
