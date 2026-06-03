<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Compdec\Enums\TipoAnexo;
use App\Modules\Compdec\Models\CompdecAnexo;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompdecAnexo>
 */
class CompdecAnexoFactory extends Factory
{
    protected $model = CompdecAnexo::class;

    public function definition(): array
    {
        $tipo = $this->faker->randomElement(TipoAnexo::cases());

        return [
            'orgao_id' => Orgao::factory()->compdec(),
            'tipo' => $tipo->value,
            'titulo' => $tipo->label() . ' ' . $this->faker->numerify('###/####'),
            'descricao' => $this->faker->optional()->sentence(),
            'numero' => $this->faker->numerify('###/####'),
            'data_emissao' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
            'data_validade' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
            'legacy_arquivo' => null,
            'legacy_id' => null,
        ];
    }

    public function vigente(): static
    {
        return $this->state(fn (): array => [
            'data_validade' => now()->addYears(2),
        ]);
    }

    public function vencido(): static
    {
        return $this->state(fn (): array => [
            'data_validade' => now()->subMonths(2),
        ]);
    }

    public function proximoVencer(): static
    {
        return $this->state(fn (): array => [
            'data_validade' => now()->addDays(15),
        ]);
    }

    public function semValidade(): static
    {
        return $this->state(fn (): array => [
            'data_validade' => null,
        ]);
    }
}
