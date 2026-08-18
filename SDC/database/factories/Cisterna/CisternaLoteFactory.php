<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Models\CisternaLote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CisternaLote>
 */
class CisternaLoteFactory extends Factory
{
    protected $model = CisternaLote::class;

    public function definition(): array
    {
        return [
            'nome' => 'Lote '.$this->faker->unique()->numerify('###/2026'),
            'data' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'observacao' => null,
            'legacy_id' => null,
        ];
    }
}
