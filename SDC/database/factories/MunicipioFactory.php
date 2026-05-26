<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

class MunicipioFactory extends Factory
{
    protected $model = Municipio::class;

    public function definition(): array
    {
        return [
            'codigo_ibge' => (string) $this->faker->unique()->numberBetween(1000000, 9999999),
            'nome' => $this->faker->city(),
            'uf' => 'MG',
            'regiao' => 'Sudeste',
            'mesorregiao' => null,
            'microrregiao' => null,
            'latitude' => $this->faker->latitude(-22.0, -14.0),
            'longitude' => $this->faker->longitude(-50.0, -40.0),
        ];
    }
}
