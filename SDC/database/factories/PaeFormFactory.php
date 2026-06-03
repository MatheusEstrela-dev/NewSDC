<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Pae\Models\PaeForm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaeFormFactory extends Factory
{
    protected $model = PaeForm::class;

    public function definition(): array
    {
        return [
            'uuid_publico'  => (string) Str::uuid(),
            'status'        => 'RASCUNHO',
            'barragem_nome' => $this->faker->words(3, true),
        ];
    }
}
