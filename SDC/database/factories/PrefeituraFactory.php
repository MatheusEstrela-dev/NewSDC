<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prefeitura>
 */
class PrefeituraFactory extends Factory
{
    protected $model = Prefeitura::class;

    public function definition(): array
    {
        return [
            'municipio_id' => null,
            'prefeito_nome' => $this->faker->name(),
            'prefeito_telefone' => $this->faker->numerify('(##) ####-####'),
            'prefeito_celular' => $this->faker->numerify('(##) #####-####'),
            'prefeito_email' => $this->faker->safeEmail(),
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->word(),
            'cep' => $this->faker->numerify('#####-###'),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'inss_tem_cobranca' => $this->faker->boolean(30),
            'inss_aliquota' => null,
            'inss_lei_cobranca' => null,
            'inss_responsavel' => null,
            'legacy_id' => null,

            // Contato institucional da prefeitura, distinto do contato do prefeito.
            // Os secundarios ficam null de proposito: no legado a maioria dos
            // municipios so tem o primeiro preenchido, e teste de relatorio precisa
            // exercitar esse caso.
            'prefeito_partido' => $this->faker->randomElement(['PSD', 'MDB', 'PT', 'PL', 'PSDB']),
            'email_prefeitura' => $this->faker->safeEmail(),
            'email_prefeitura_2' => null,
            'email_prefeitura_3' => null,
            'tel_prefeitura' => $this->faker->numerify('(##) ####-####'),
            'tel_prefeitura_2' => null,
            'fax_prefeitura' => $this->faker->numerify('(##) ####-####'),
        ];
    }

    public function comInss(): static
    {
        return $this->state(fn () => [
            'inss_tem_cobranca' => true,
            'inss_aliquota' => $this->faker->randomFloat(2, 1, 5),
            'inss_lei_cobranca' => 'Lei municipal ' . $this->faker->numerify('###/####'),
            'inss_responsavel' => $this->faker->name(),
        ]);
    }
}
