<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Plantao\Models\TipoTurno;
use Illuminate\Database\Seeder;

/**
 * Horarios praticados hoje. Ponto de partida da tabela -- dali em diante o
 * cadastro e feito pela tela, sem deploy.
 *
 * updateOrCreate por `codigo`: o seeder roda em banco compartilhado e nao pode
 * duplicar tipo nem sobrescrever horario que o usuario tenha ajustado depois
 * (por isso so os campos estruturais sao reescritos).
 */
class PlantaoTiposTurnoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => 'DIURNO',
                'nome' => 'Diurno 10h',
                'hora_inicio' => '06:00',
                'hora_fim' => '16:00',
                'vira_dia' => false,
                'escalavel' => true,
                'cor' => '#f59e0b',
                'ordem' => 1,
            ],
            [
                'codigo' => 'NOTURNO',
                'nome' => 'Noturno 10h',
                'hora_inicio' => '16:00',
                'hora_fim' => '02:00',
                'vira_dia' => true,
                'escalavel' => true,
                'cor' => '#4f46e5',
                'ordem' => 2,
            ],
            [
                'codigo' => 'DIURNO_12H',
                'nome' => 'Diurno 12h',
                'hora_inicio' => '08:00',
                'hora_fim' => '20:00',
                'vira_dia' => false,
                'escalavel' => true,
                'cor' => '#10b981',
                'ordem' => 3,
            ],
            [
                'codigo' => 'NOTURNO_12H',
                'nome' => 'Noturno 12h',
                'hora_inicio' => '20:00',
                'hora_fim' => '08:00',
                'vira_dia' => true,
                'escalavel' => true,
                'cor' => '#7c3aed',
                'ordem' => 4,
            ],
            [
                // Sem hora fixa: existe para abrir turno fora de escala, e por
                // isso nao e escalavel.
                'codigo' => 'EXTRAORDINARIO',
                'nome' => 'Extraordinario',
                'hora_inicio' => null,
                'hora_fim' => null,
                'vira_dia' => false,
                'escalavel' => false,
                'cor' => '#64748b',
                'ordem' => 9,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoTurno::updateOrCreate(
                ['codigo' => $tipo['codigo']],
                $tipo + ['ativo' => true],
            );
        }

        $this->command?->info('Tipos de turno: '.count($tipos).' garantidos.');
    }
}
