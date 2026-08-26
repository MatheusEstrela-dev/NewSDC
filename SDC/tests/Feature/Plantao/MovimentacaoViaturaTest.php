<?php

declare(strict_types=1);

namespace Tests\Feature\Plantao;

use App\Models\User;
use App\Modules\Plantao\Enums\NivelCombustivel;
use App\Modules\Plantao\Enums\StatusMovimentacao;
use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Exceptions\MovimentacaoInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\MovimentacaoViaturaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MovimentacaoViaturaTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): MovimentacaoViaturaService
    {
        return app(MovimentacaoViaturaService::class);
    }

    public function test_saida_marca_viatura_em_transito(): void
    {
        $viatura = Viatura::factory()->create([
            'hodometro_atual' => 112_600,
            'status' => StatusViatura::DISPONIVEL,
        ]);
        $condutor = User::factory()->create(['name' => 'Sgt Egidio']);

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
            'destino' => 'Sete Lagoas',
        ]);

        $this->assertSame(StatusMovimentacao::EM_TRANSITO, $mov->status);
        $this->assertSame('Sgt Egidio', $mov->condutor_nome);

        $viatura->refresh();
        $this->assertSame(StatusViatura::EM_TRANSITO, $viatura->status);
        $this->assertSame(112_640, $viatura->hodometro_atual);
        $this->assertSame('Sgt Egidio', $viatura->ultimo_condutor_nome);
        $this->assertSame($condutor->id, $viatura->ultimo_condutor_id);
    }

    public function test_retorno_atualiza_estado_corrente_da_viatura(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_600]);
        $condutor = User::factory()->create(['name' => 'Sgt Egidio']);

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $mov = $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 112_799,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
            'alteracoes' => null,
        ]);

        $this->assertSame(StatusMovimentacao::RETORNADA, $mov->status);

        $viatura->refresh();
        $this->assertSame(StatusViatura::DISPONIVEL, $viatura->status);
        $this->assertSame(112_799, $viatura->hodometro_atual);
        $this->assertSame(NivelCombustivel::QUARTO_3, $viatura->nivel_combustivel);
    }

    public function test_retorno_com_hodometro_menor_que_a_saida_e_rejeitado(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_600]);
        $condutor = User::factory()->create();

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_640,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 112_600,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
        ]);
    }

    public function test_segunda_saida_sem_retorno_e_rejeitada(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create();

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_010,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
    }

    public function test_saida_de_viatura_em_manutencao_e_rejeitada(): void
    {
        $viatura = Viatura::factory()->emManutencao()->create(['hodometro_atual' => 90_000]);
        $condutor = User::factory()->create();

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 90_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
    }

    public function test_saida_com_hodometro_menor_que_o_corrente_e_rejeitada(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 112_799]);
        $condutor = User::factory()->create();

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 112_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);
    }

    public function test_retorno_em_movimentacao_ja_fechada_e_rejeitado(): void
    {
        $viatura = Viatura::factory()->create(['hodometro_atual' => 100_000]);
        $condutor = User::factory()->create();

        $mov = $this->service()->registrarSaida($viatura->id, [
            'condutor_id' => $condutor->id,
            'saida_hodometro' => 100_000,
            'saida_combustivel' => NivelCombustivel::QUARTO_4->value,
        ]);

        $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 100_100,
            'retorno_combustivel' => NivelCombustivel::QUARTO_3->value,
        ]);

        $this->expectException(MovimentacaoInvalidaException::class);

        $this->service()->registrarRetorno($mov->id, [
            'retorno_hodometro' => 100_200,
            'retorno_combustivel' => NivelCombustivel::QUARTO_2->value,
        ]);
    }
}
