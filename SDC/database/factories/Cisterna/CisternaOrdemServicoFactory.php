<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CisternaOrdemServico>
 */
class CisternaOrdemServicoFactory extends Factory
{
    protected $model = CisternaOrdemServico::class;

    public function definition(): array
    {
        return [
            'lote_id' => CisternaLote::factory(),
            'nome' => 'OS '.$this->faker->unique()->numerify('####'),
            'observacao' => null,
            'legacy_id' => null,
        ];
    }
}
