<?php

declare(strict_types=1);

namespace Database\Factories\Pae;

use App\Models\Municipio;
use App\Modules\Pae\Models\PaeEmpdor;
use App\Modules\Pae\Models\PaeEmpnto;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeEmpntoFactory extends Factory
{
    protected $model = PaeEmpnto::class;

    public function definition(): array
    {
        return [
            'nome' => 'Barragem ' . $this->faker->lastName(),
            'status' => 'OPERACAO',
            'municipio_id' => Municipio::query()->inRandomOrder()->first()?->id
                ?? Municipio::factory(),
            'pae_empdor_id' => PaeEmpdor::factory(),
            'm_construcao' => $this->faker->randomElement([
                'Alteamento a Montante',
                'Alteamento a Jusante',
                'Linha de Centro',
            ]),
            'material' => 'Rejeitos',
            'finalidade' => 'Contencao de Rejeitos',
            'volume' => $this->faker->randomFloat(2, 1000, 50000000),
            'pop_zas' => $this->faker->numberBetween(0, 5000),
            'orgao_fisc' => 'ANM',
            'coordenador' => $this->faker->name(),
            'tel_coordenador' => $this->faker->phoneNumber(),
            'email_coord' => $this->faker->safeEmail(),
            'user_update' => $this->faker->name(),
        ];
    }
}
