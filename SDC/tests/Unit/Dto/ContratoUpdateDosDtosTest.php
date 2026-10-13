<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Compdec\DTOs\EquipeDTO;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use App\Modules\Decretacoes\Enums\StatusProcesso;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Tdap\DTOs\AtaDTO;
use Tests\TestCase;

/**
 * Teste unitario da camada DTO (#67), sem banco: o array que cada toArray()
 * entrega ao update/create do Model. Os testes de persistencia estao em
 * tests/Feature/{Rat,Decretacoes,Dto}.
 */
class ContratoUpdateDosDtosTest extends TestCase
{
    // ── RAT ─────────────────────────────────────────────────────────────────

    public function test_vistoria_entrega_so_o_que_veio_com_os_tipos_certos(): void
    {
        $dados = RatVistoriaDTO::fromArray([
            'solicitante'     => ['nome' => '', 'cpf' => '12345678901'],
            'patologias'      => ['rachaduras' => 'false'],
            'v_numero_moradores' => '4',
            'orgaos_acionados' => ['pm' => true, 'bm' => 'false'],
        ])->toArray();

        $this->assertSame([
            'v_solicitante_nome'     => null,
            'v_solicitante_cpf'      => '12345678901',
            'v_numero_moradores'     => 4,
            'v_patologia_rachaduras' => false,
            'v_orgao_pm'             => 'sim',
            'v_orgao_bm'             => null,
        ], $this->ordenado($dados, ['v_solicitante_nome', 'v_solicitante_cpf', 'v_numero_moradores', 'v_patologia_rachaduras', 'v_orgao_pm', 'v_orgao_bm']));
        $this->assertCount(6, $dados, 'nenhuma outra coluna vai ao update');
    }

    public function test_dados_gerais_nao_inventa_tem_vistoria(): void
    {
        $this->assertArrayNotHasKey('tem_vistoria', RatDadosGeraisDTO::fromArray(['local' => ['bairro' => 'Centro']])->toArray());
        $this->assertFalse(RatDadosGeraisDTO::fromArray(['tem_vistoria' => '0'])->toArray()['tem_vistoria']);
    }

    public function test_envolvido_aceita_uuid_e_bool_em_texto(): void
    {
        $dto = RatEnvolvidoDTO::fromArray(['id' => '0199a3b2-0000-7000-8000-000000000001', 'p_turista' => 'false']);

        $this->assertSame('0199a3b2-0000-7000-8000-000000000001', $dto->id);
        $this->assertSame(['p_turista' => false], $dto->toArray());
    }

    public function test_relatos_ausentes_ficam_null_e_listas_ganham_seq(): void
    {
        $relatos = RatRelatosDTO::fromArray(['envolvidos' => [['p_nome_completo' => 'A'], ['p_nome_completo' => 'B']]]);

        $this->assertNull($relatos->dadosGerais);
        $this->assertNull($relatos->vistoria);
        $this->assertNull($relatos->recursos);
        $this->assertSame([1, 2], array_map(fn ($e) => $e->seq, $relatos->envolvidos));
        $this->assertTrue($relatos->temConteudo);
    }

    // ── Decretacoes ─────────────────────────────────────────────────────────

    public function test_processo_update_parcial_nao_toca_municipios_nem_prazo(): void
    {
        $dto = ProcessoRequestDTO::fromValidated(['observacoes' => '', 'status' => 'Registro'], parcial: true);

        $this->assertSame(['status' => 'Registro', 'observacoes' => null], $this->ordenado($dto->allData, ['status', 'observacoes']));
        $this->assertCount(2, $dto->allData);
        $this->assertNull($dto->municipios, 'municipio ausente = nao sincronizar');
    }

    public function test_processo_create_nasce_em_registro_com_prazo_padrao(): void
    {
        $dto = ProcessoRequestDTO::fromValidated(['origem' => 'municipal', 'municipio_id' => '10', 'data_entrada' => '2026-01-01']);

        $this->assertSame(StatusProcesso::REGISTRO->value, $dto->allData['status']);
        $this->assertSame('MUNICIPAL', $dto->allData['processo']);
        $this->assertSame(180, $dto->allData['prazo_vigencia']);
        $this->assertSame([10], $dto->municipios);
    }

    // ── Compdec, Cisterna, Tdap ─────────────────────────────────────────────

    public function test_ausentes_nao_viram_default_no_update(): void
    {
        $orgao = OrgaoDTO::fromRequest(['codigo' => 'C1', 'nome' => 'N', 'tipo' => 'compdec', 'status' => 'ativo'])->toArray();
        foreach (['tem_viatura', 'tem_simulado', 'qtd_efetivo', 'qtd_nupdec', 'legacy_id'] as $coluna) {
            $this->assertArrayNotHasKey($coluna, $orgao, "$coluna ausente nao pode ir ao update");
        }

        $this->assertArrayNotHasKey('ativo', EquipeDTO::fromRequest(['nome' => 'A', 'funcao' => 'agente'])->toArray());
        $this->assertFalse(EquipeDTO::fromRequest(['nome' => 'A', 'funcao' => 'agente', 'ativo' => '0'])->toArray()['ativo']);

        $comunidade = ComunidadeDTO::deValidados(['municipio_id' => 1, 'nome' => 'X'])->toArray();
        $this->assertArrayNotHasKey('ativa', $comunidade);
        $this->assertArrayNotHasKey('legacy_id', $comunidade);

        $this->assertArrayNotHasKey('ativo', AtaDTO::fromRequest(['numero' => '1', 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'])->toArray());
    }

    public function test_legacy_id_vai_quando_vem_da_carga_do_legado(): void
    {
        $this->assertSame(77, OrdemServicoDTO::deValidados(['lote_id' => 1, 'nome' => 'OS', 'legacy_id' => '77'])->toArray()['legacy_id']);
    }

    /** @param list<string> $chaves */
    private function ordenado(array $dados, array $chaves): array
    {
        return array_merge(array_flip($chaves), array_intersect_key($dados, array_flip($chaves)));
    }
}
