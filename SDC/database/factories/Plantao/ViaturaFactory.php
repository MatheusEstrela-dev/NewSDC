<?php

declare(strict_types=1);

namespace Database\Factories\Plantao;

use App\Modules\Plantao\Enums\LocalizacaoViatura;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use Illuminate\Database\Eloquent\Factories\Factory;

class ViaturaFactory extends Factory
{
    protected $model = Viatura::class;

    public function definition(): array
    {
        return [
            'prefixo' => 'SW4',
            'placa' => strtoupper($this->faker->unique()->bothify('QM?-####')),
            'marca' => 'Toyota',
            'modelo' => 'Hilux SW4',
            'localizacao' => LocalizacaoViatura::PREDIO_ALTEROSAS,
            'exclusiva_sobreaviso' => false,
            'status' => StatusViatura::DISPONIVEL,
            'hodometro_atual' => $this->faker->numberBetween(50_000, 150_000),
            'nivel_combustivel' => NivelCombustivel::QUARTO_4,
            'ativo' => true,
        ];
    }

    public function emManutencao(): static
    {
        return $this->state(fn() => ['status' => StatusViatura::MANUTENCAO]);
    }

    public function exclusivaSobreaviso(): static
    {
        return $this->state(fn() => ['exclusiva_sobreaviso' => true]);
    }
}
