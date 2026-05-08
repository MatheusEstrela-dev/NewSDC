<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompdecPlanoContingencia>
 */
class CompdecPlanoContingenciaFactory extends Factory
{
    protected $model = CompdecPlanoContingencia::class;

    public function definition(): array
    {
        return [
            'orgao_id' => Orgao::factory()->compdec(),
            'versao' => 'v' . $this->faker->numberBetween(1, 5) . '.' . $this->faker->numberBetween(0, 9),
            'tamanho_bytes' => $this->faker->numberBetween(50_000, 5_000_000),
            'observacoes' => $this->faker->optional()->sentence(),
            'ativo' => false,
            'aprovado_em' => null,
            'aprovado_por' => null,
            'legacy_arquivo' => null,
            'legacy_id' => null,
        ];
    }

    public function ativo(): static
    {
        return $this->state(fn (): array => ['ativo' => true]);
    }

    public function aprovado(): static
    {
        return $this->state(fn (): array => [
            'aprovado_em' => now()->subDays($this->faker->numberBetween(1, 60)),
            'aprovado_por' => 1,
        ]);
    }
}
