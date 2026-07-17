<?php

namespace Database\Factories\Pae;

use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeNotificacao;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeNotificacaoFactory extends Factory
{
    protected $model = PaeNotificacao::class;

    public function definition(): array
    {
        return [
            'pae_analise_id' => PaeAnalise::factory(),
            'num_sei'        => 'SEI-' . $this->faker->numerify('####.######/####-##'),
            'dt_notificacao' => now()->toDateString(),
            'prorrogacao'    => false,
        ];
    }
}
