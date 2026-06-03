<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Cisterna\Enums\StatusCisterna;
use App\Modules\Cisterna\Enums\TipoCisterna;
use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cisterna>
 */
class CisternaFactory extends Factory
{
    protected $model = Cisterna::class;

    public function definition(): array
    {
        return [
            'municipio_id' => DB::table('municipios')->inRandomOrder()->value('id') ?? 1,
            'codigo' => 'CIST-' . strtoupper(Str::random(8)),
            'nome' => 'Cisterna ' . $this->faker->city(),
            'endereco' => $this->faker->address(),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'capacidade_litros' => $this->faker->randomElement([5000, 10000, 16000, 20000, 50000]),
            'tipo' => TipoCisterna::COMUNITARIA->value,
            'status' => StatusCisterna::ATIVA->value,
            'data_instalacao' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
            'responsavel_nome' => $this->faker->name(),
            'responsavel_telefone' => $this->faker->numerify('(##) #####-####'),
            'observacoes' => null,
            'legacy_id' => null,
        ];
    }

    public function ativa(): static
    {
        return $this->state(fn (): array => ['status' => StatusCisterna::ATIVA->value]);
    }

    public function pendente(): static
    {
        return $this->state(fn (): array => ['status' => StatusCisterna::PENDENTE->value]);
    }

    public function inativa(): static
    {
        return $this->state(fn (): array => ['status' => StatusCisterna::INATIVA->value]);
    }

    public function emObras(): static
    {
        return $this->state(fn (): array => ['status' => StatusCisterna::EM_OBRAS->value]);
    }

    public function comunitaria(): static
    {
        return $this->state(fn (): array => ['tipo' => TipoCisterna::COMUNITARIA->value]);
    }

    public function individual(): static
    {
        return $this->state(fn (): array => ['tipo' => TipoCisterna::INDIVIDUAL->value]);
    }

    public function escolar(): static
    {
        return $this->state(fn (): array => ['tipo' => TipoCisterna::ESCOLAR->value]);
    }
}
