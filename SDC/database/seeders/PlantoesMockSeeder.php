<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Models\User;
use Carbon\Carbon;

class PlantoesMockSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("\nCriando Plantões mockados...\n");

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            Plantao::truncate();
            DB::statement('PRAGMA foreign_keys=ON');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Plantao::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            Plantao::truncate();
        }

        $users = User::limit(3)->get();
        if ($users->isEmpty()) {
            $this->command->warn('Nenhum usuário encontrado para associar aos plantões.');
            return;
        }

        $plantoesData = [];
        
        // Criar plantões retroativos e futuros
        for ($i = -5; $i <= 5; $i++) {
            $data = Carbon::now()->addDays($i);
            
            // Plantão Diurno
            $plantoesData[] = [
                'data' => $data->format('Y-m-d'),
                'plantonista_id' => $users->random()->id,
                'plantonista_nome' => $users->random()->name,
                'periodo' => PeriodoPlantao::DIURNO->value,
                'status' => $i < 0 ? StatusPlantao::FINALIZADO->value : StatusPlantao::ATIVO->value,
                'observacoes' => $i === 0 ? 'Plantão transcorrendo sem alterações.' : null,
            ];

            // Plantão Noturno
            $plantoesData[] = [
                'data' => $data->format('Y-m-d'),
                'plantonista_id' => $users->random()->id,
                'plantonista_nome' => $users->random()->name,
                'periodo' => PeriodoPlantao::NOTURNO->value,
                'status' => $i <= 0 ? ($i === 0 ? StatusPlantao::ATIVO->value : StatusPlantao::FINALIZADO->value) : StatusPlantao::ATIVO->value,
                'observacoes' => null,
            ];
            
            // Plantão Extraordinário ocasional
            if (rand(1, 10) > 7) {
                $plantoesData[] = [
                    'data' => $data->format('Y-m-d'),
                    'plantonista_id' => $users->random()->id,
                    'plantonista_nome' => $users->random()->name,
                    'periodo' => PeriodoPlantao::EXTRAORDINARIO->value,
                    'status' => $i < 0 ? StatusPlantao::FINALIZADO->value : StatusPlantao::ATIVO->value,
                    'observacoes' => 'Convocado devido alerta vermelho de chuvas fortes.',
                ];
            }
        }

        foreach ($plantoesData as $data) {
            Plantao::create($data);
        }

        $this->command->info("Plantões criados com sucesso: " . count($plantoesData));
    }
}
