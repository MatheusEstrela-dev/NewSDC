<?php

declare(strict_types=1);

namespace Database\Factories\Plantao;

use App\Models\User;
use App\Modules\Plantao\Enums\PeriodoPlantao;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Models\Plantao;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantaoFactory extends Factory
{
    protected $model = Plantao::class;

    public function definition(): array
    {
        return [
            'plantonista_id' => User::factory(),
            'plantonista_nome' => $this->faker->name(),
            'data' => now()->toDateString(),
            'periodo' => PeriodoPlantao::DIURNO,
            'status' => StatusPlantao::ATIVO,
            'localizacao' => 'Predio Alterosas',
        ];
    }

    public function pendenteAceite(): static
    {
        return $this->state(fn() => [
            'status' => StatusPlantao::PENDENTE_ACEITE,
            'encerrado_em' => now(),
        ]);
    }

    public function finalizado(): static
    {
        return $this->state(fn() => [
            'status' => StatusPlantao::FINALIZADO,
            'encerrado_em' => now()->subHour(),
            'aceito_em' => now(),
        ]);
    }
}
