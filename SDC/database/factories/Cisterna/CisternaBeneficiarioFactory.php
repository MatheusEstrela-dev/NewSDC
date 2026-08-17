<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<CisternaBeneficiario>
 */
class CisternaBeneficiarioFactory extends Factory
{
    protected $model = CisternaBeneficiario::class;

    public function definition(): array
    {
        $renda = $this->faker->randomFloat(2, 200, 3000);
        $pessoas = $this->faker->numberBetween(1, 8);
        $comprimento = $this->faker->randomFloat(2, 4, 20);
        $largura = $this->faker->randomFloat(2, 3, 12);

        return [
            // CPF sem mascara, 11 digitos, unico.
            'cpf' => $this->faker->unique()->numerify('###########'),
            'nome' => $this->faker->name(),
            'telefone' => $this->faker->numerify('(31) 9####-####'),
            // Maior de 18 anos, conforme a validacao do legado.
            'data_nascimento' => $this->faker->dateTimeBetween('-80 years', '-18 years'),
            'cadastro_unico' => $this->faker->numerify('############'),
            'municipio_id' => DB::table('municipios')->inRandomOrder()->value('id'),
            'comunidade_id' => null,
            'endereco' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'ordem_servico_id' => null,
            'situacao_analise' => SituacaoAnalise::EM_EDICAO->value,
            'situacao_obra' => SituacaoObra::PROCESSAMENTO->value,
            'ranqueamento_ordem' => null,
            'qtd_pessoas' => $pessoas,
            'renda' => $renda,
            'renda_per_capita' => round($renda / $pessoas, 2),
            'possui_deficiencia' => false,
            'possui_crianca' => false,
            'data_nascimento_crianca' => null,
            'possui_idoso' => false,
            'chefiada_mulher' => false,
            'tipo_moradia' => 'propria',
            'comprimento_telhado' => $comprimento,
            'largura_telhado' => $largura,
            'area_telhado' => round($comprimento * $largura, 2),
            'comprimento_testada' => $comprimento,
            'num_caidas_telhado' => $this->faker->numberBetween(1, 4),
            'cobertura_telhado' => 'ceramica',
            'possui_fogao_lenha' => false,
            'atendido_por_pipa' => false,
            'agente_nome' => $this->faker->name(),
            'agente_cpf' => $this->faker->numerify('###########'),
            'engenheiro_nome' => $this->faker->name(),
            'engenheiro_crea' => 'MG-'.$this->faker->numerify('######'),
            'observacoes' => null,
            'legacy_id' => null,
        ];
    }

    public function aprovado(): static
    {
        return $this->state(fn (): array => [
            'situacao_analise' => SituacaoAnalise::APROVADO->value,
        ]);
    }

    public function instalado(): static
    {
        return $this->state(fn (): array => [
            'situacao_analise' => SituacaoAnalise::APROVADO->value,
            'situacao_obra' => SituacaoObra::INSTALADO->value,
        ]);
    }
}
