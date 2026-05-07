<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Compdec\Enums\StatusOrgao;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Orgao>
 */
class OrgaoFactory extends Factory
{
    protected $model = Orgao::class;

    public function definition(): array
    {
        $tipo = $this->faker->randomElement(TipoOrgao::cases());
        $codigo = strtoupper($tipo->value) . '-' . strtoupper(Str::random(8));

        return [
            'codigo' => $codigo,
            'nome' => $tipo->label() . ' ' . $this->faker->city(),
            'tipo' => $tipo->value,
            'municipio_id' => null,
            'orgao_superior_id' => null,
            'status' => StatusOrgao::ATIVO->value,

            'email' => $this->faker->safeEmail(),
            'email_secundario' => null,
            'email_terciario' => null,
            'telefone' => $this->faker->numerify('(##) ####-####'),
            'telefone_secundario' => null,
            'fax' => null,
            'endereco' => $this->faker->address(),

            'responsavel_nome' => $this->faker->name(),
            'responsavel_cpf' => $this->faker->numerify('###.###.###-##'),
            'responsavel_telefone' => $this->faker->numerify('(##) #####-####'),
            'responsavel_email' => $this->faker->safeEmail(),

            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),

            'lei_criacao_numero' => $this->faker->numerify('###/####'),
            'lei_criacao_data' => $this->faker->dateTimeBetween('-15 years', '-1 year'),
            'decreto_numero' => $this->faker->numerify('##/####'),
            'decreto_data' => $this->faker->dateTimeBetween('-15 years', '-1 year'),
            'portaria_numero' => $this->faker->numerify('###/####'),
            'portaria_data' => $this->faker->dateTimeBetween('-3 years', 'now'),

            'qtd_efetivo' => $this->faker->numberBetween(1, 30),
            'qtd_nupdec' => $this->faker->numberBetween(0, 5),

            'tem_sede_propria' => $this->faker->boolean(),
            'tem_viatura' => $this->faker->boolean(),
            'tem_plano_contingencia' => false,
            'tem_mapeamento_risco' => $this->faker->boolean(),
            'tem_simulado' => $this->faker->boolean(),
            'tem_cartao_pdc' => $this->faker->boolean(),

            'metadata' => [
                'capacidades' => [
                    'tem_computador' => $this->faker->boolean(),
                    'tem_curso_gestao' => $this->faker->boolean(),
                    'data_curso_gestao' => null,
                    'tem_curso_sco' => $this->faker->boolean(),
                    'data_curso_sco' => null,
                    'tem_workshop_pdc' => $this->faker->boolean(),
                    'data_workshop_pdc' => null,
                ],
            ],

            'legacy_id' => null,
        ];
    }

    public function cedec(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoOrgao::CEDEC->value,
            'codigo' => 'CEDEC-' . strtoupper(Str::random(4)),
            'nome' => 'Coordenadoria Estadual de Defesa Civil ' . $this->faker->stateAbbr(),
            'municipio_id' => null,
            'orgao_superior_id' => null,
        ]);
    }

    public function redec(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoOrgao::REDEC->value,
            'codigo' => 'REDEC-' . strtoupper(Str::random(4)),
            'nome' => 'Regional ' . $this->faker->city(),
            'municipio_id' => null,
        ]);
    }

    public function compdec(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoOrgao::COMPDEC->value,
            'codigo' => 'COMPDEC-' . strtoupper(Str::random(4)),
            'nome' => 'COMPDEC ' . $this->faker->city(),
        ]);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['status' => StatusOrgao::INATIVO->value]);
    }
}
