<?php

declare(strict_types=1);

namespace Database\Factories\Pae;

use App\Modules\Pae\Models\PaeEmpdor;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeEmpdorFactory extends Factory
{
    protected $model = PaeEmpdor::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->company(),
            'cnpj' => $this->faker->unique()->numerify('##############'),
            'status' => 'ATIVO',
        ];
    }
}
