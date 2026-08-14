<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Models\CisternaComunidade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<CisternaComunidade>
 */
class CisternaComunidadeFactory extends Factory
{
    protected $model = CisternaComunidade::class;

    public function definition(): array
    {
        return [
            // O banco de dev/teste ja tem os 853 municipios de MG seedados.
            'municipio_id' => DB::table('municipios')->inRandomOrder()->value('id'),
            'nome' => 'Comunidade '.$this->faker->unique()->lastName(),
            'ativa' => true,
            'legacy_id' => null,
        ];
    }
}
