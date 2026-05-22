<?php

declare(strict_types=1);

namespace Database\Factories\Pae;

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeProtocoloFactory extends Factory
{
    protected $model = PaeProtocolo::class;

    public function definition(): array
    {
        return [
            'num_protocolo' => $this->faker->unique()->numerify('2024.##.##.####'),
            'sigibar' => $this->faker->unique()->numerify('SIGIBAR-######'),
            'status' => PaeProtocoloStatus::NOVO->value,
            'arquivado' => false,
            'dt_entrada' => now()->toDateString(),
        ];
    }
}
