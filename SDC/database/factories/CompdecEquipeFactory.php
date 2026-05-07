<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Compdec\Enums\FuncaoEquipe;
use App\Modules\Compdec\Models\CompdecEquipe;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompdecEquipe>
 */
class CompdecEquipeFactory extends Factory
{
    protected $model = CompdecEquipe::class;

    public function definition(): array
    {
        return [
            'orgao_id' => Orgao::factory()->compdec(),
            'nome' => $this->faker->name(),
            'funcao' => FuncaoEquipe::AGENTE->value,
            'telefone' => $this->faker->numerify('(##) ####-####'),
            'celular' => $this->faker->numerify('(##) #####-####'),
            'email' => $this->faker->safeEmail(),
            'cpf' => $this->faker->numerify('###.###.###-##'),
            'ativo' => true,
            'ordem' => 0,
            'observacoes' => null,
            'legacy_id' => null,
        ];
    }

    public function coordenador(): static
    {
        return $this->state(fn (): array => [
            'funcao' => FuncaoEquipe::COORDENADOR->value,
            'ordem' => 0,
        ]);
    }

    public function agente(): static
    {
        return $this->state(fn (): array => ['funcao' => FuncaoEquipe::AGENTE->value]);
    }

    public function inativo(): static
    {
        return $this->state(fn (): array => ['ativo' => false]);
    }
}
