<?php

declare(strict_types=1);

namespace Database\Factories\Cisterna;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CisternaVistoria>
 */
class CisternaVistoriaFactory extends Factory
{
    protected $model = CisternaVistoria::class;

    public function definition(): array
    {
        return [
            'beneficiario_id' => CisternaBeneficiario::factory(),
            'etapa' => EtapaVistoria::FORNECEDOR->value,
            'numero_instalacao' => $this->faker->unique()->numberBetween(1, 999999),
            'engenheiro_nome' => $this->faker->name(),
            'engenheiro_crea' => 'MG-'.$this->faker->numerify('######'),
            'engenheiro_art' => null,
            'data_relatorio' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'local_relatorio' => $this->faker->city(),
            'processo_sei' => null,
            'contrato' => null,
            'empenho' => null,
            'placa_obras' => null,
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->citySuffix(),
            'latitude' => $this->faker->latitude(-23, -19),
            'longitude' => $this->faker->longitude(-50, -40),
            'observacoes' => null,
            'concluida_em' => null,
            'legacy_id' => null,
        ];
    }

    public function daEtapa(EtapaVistoria $etapa): static
    {
        return $this->state(fn (): array => [
            'etapa' => $etapa->value,
            // Somente a etapa do fornecedor aloca numero de instalacao.
            'numero_instalacao' => $etapa === EtapaVistoria::FORNECEDOR
                ? $this->faker->unique()->numberBetween(1, 999999)
                : null,
            'processo_sei' => $etapa === EtapaVistoria::CEDEC ? 'SEI-'.$this->faker->numerify('######') : null,
            'contrato' => $etapa === EtapaVistoria::CEDEC ? $this->faker->numerify('####/2026') : null,
            'empenho' => $etapa === EtapaVistoria::CEDEC ? $this->faker->numerify('######') : null,
            'placa_obras' => $etapa === EtapaVistoria::CEDEC ? 1 : null,
        ]);
    }

    public function concluida(): static
    {
        return $this->state(fn (): array => ['concluida_em' => now()]);
    }
}
