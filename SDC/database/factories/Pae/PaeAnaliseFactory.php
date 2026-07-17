<?php

namespace Database\Factories\Pae;

use App\Modules\Pae\Models\PaeAnalise;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaeAnaliseFactory extends Factory
{
    protected $model = PaeAnalise::class;

    public function definition(): array
    {
        return [
            'pae_protocolo_id' => PaeProtocolo::factory(),
            'status'           => 'EM_ANDAMENTO',
            'parecer'          => '',
        ];
    }
}
