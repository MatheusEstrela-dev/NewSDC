<?php

declare(strict_types=1);

namespace Tests\Feature\Pae;

use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Services\PaeProtocoloService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaeProtocoloNumeracaoTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): PaeProtocoloService
    {
        return app(PaeProtocoloService::class);
    }

    /**
     * Proximo sequencial global atual, sem persistir nada. gerarNumProtocolo()
     * apenas le o maior NNNN e devolve a string, sem inserir protocolo, entao
     * pode ser usado para descobrir o baseline independente do estado da base.
     */
    private function proximoSequencial(): int
    {
        preg_match('/-(\d{4})-\d{3}$/', $this->service()->gerarNumProtocolo(), $m);

        return (int) $m[1];
    }

    public function test_gera_numero_no_formato_data_sequencial_versao(): void
    {
        $num = $this->service()->gerarNumProtocolo();

        $hoje = now()->format('d.m.Y');
        $this->assertMatchesRegularExpression(
            '/^' . preg_quote($hoje, '/') . '-\d{4}-001$/',
            $num
        );
    }

    public function test_sequencial_global_incrementa_a_partir_do_maior(): void
    {
        $n = $this->proximoSequencial();
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => sprintf('%s-%04d-001', $hoje, $n)]);

        $this->assertSame(
            sprintf('%s-%04d-001', $hoje, $n + 1),
            $this->service()->gerarNumProtocolo()
        );
    }

    public function test_sequencial_nao_reseta_entre_datas(): void
    {
        // Regressao do bug: o NNNN e global. Um protocolo de ONTEM com sequencial
        // N faz o numero de HOJE continuar em N+1, nao voltar para 0001.
        $n = $this->proximoSequencial();
        $ontem = now()->subDay()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => sprintf('%s-%04d-001', $ontem, $n)]);

        $hoje = now()->format('d.m.Y');
        $this->assertSame(
            sprintf('%s-%04d-001', $hoje, $n + 1),
            $this->service()->gerarNumProtocolo()
        );
    }

    public function test_ignora_formato_antigo_no_calculo(): void
    {
        $n = $this->proximoSequencial();

        // Formato legado (sem os segmentos -NNNN-SSS) nao entra no calculo.
        PaeProtocolo::factory()->create(['num_protocolo' => now()->format('d.m.Y') . '.015']);

        $this->assertSame($n, $this->proximoSequencial());
    }

    public function test_versoes_relacionadas_nao_consomem_sequencial(): void
    {
        // Protocolos relacionados reaproveitam o NNNN base (variam so no sufixo
        // -SSS), portanto nao avancam o sequencial global.
        $n = $this->proximoSequencial();
        $hoje = now()->format('d.m.Y');

        PaeProtocolo::factory()->create(['num_protocolo' => sprintf('%s-%04d-001', $hoje, $n)]);
        PaeProtocolo::factory()->create(['num_protocolo' => sprintf('%s-%04d-003', $hoje, $n)]);

        $this->assertSame(
            sprintf('%s-%04d-001', $hoje, $n + 1),
            $this->service()->gerarNumProtocolo()
        );
    }
}
