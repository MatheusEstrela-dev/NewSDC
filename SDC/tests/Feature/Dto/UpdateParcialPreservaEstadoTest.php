<?php

declare(strict_types=1);

namespace Tests\Feature\Dto;

use App\Models\User;
use App\Modules\Cisterna\DTOs\ComunidadeDTO;
use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaLote;
use App\Modules\Cisterna\Services\ComunidadeService;
use App\Modules\Cisterna\Services\OrdemServicoService;
use App\Modules\Compdec\DTOs\EquipeDTO;
use App\Modules\Compdec\DTOs\OrgaoDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Services\EquipeService;
use App\Modules\Compdec\Services\OrgaoService;
use App\Modules\Tdap\DTOs\AtaDTO;
use App\Modules\Tdap\DTOs\PrestadorDTO;
use App\Modules\Tdap\Services\AtaService;
use App\Modules\Tdap\Services\PrestadorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * P0 do checklist_correcoes (#63) para Cisterna, Compdec e Tdap: em update
 * parcial, campo AUSENTE preserva o valor gravado, false explicito grava e o
 * legacy_id de registro migrado nao e apagado.
 */
class UpdateParcialPreservaEstadoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    // ── Compdec ─────────────────────────────────────────────────────────────

    public function test_equipe_inativa_continua_inativa_sem_o_campo_ativo(): void
    {
        $orgao = Orgao::factory()->create();
        $svc = app(EquipeService::class);

        $membro = $svc->criar($orgao->id, EquipeDTO::fromRequest(['nome' => 'Ana', 'funcao' => 'agente', 'ativo' => false]));
        $membro->forceFill(['legacy_id' => 991])->saveQuietly();

        $svc->atualizar($orgao->id, $membro->id, EquipeDTO::fromRequest(['nome' => 'Ana Souza', 'funcao' => 'agente']));

        $membro->refresh();
        $this->assertSame('Ana Souza', $membro->nome);
        $this->assertFalse((bool) $membro->ativo, 'editar nao pode reativar');
        $this->assertSame(991, (int) $membro->legacy_id, 'legacy_id do migrado preservado');

        $svc->atualizar($orgao->id, $membro->id, EquipeDTO::fromRequest(['nome' => 'Ana Souza', 'funcao' => 'agente', 'ativo' => true]));
        $this->assertTrue((bool) $membro->fresh()->ativo, 'valor explicito grava');
    }

    public function test_regra_de_coordenador_unico_considera_o_ativo_efetivo(): void
    {
        $orgao = Orgao::factory()->create();
        $svc = app(EquipeService::class);

        $svc->criar($orgao->id, EquipeDTO::fromRequest(['nome' => 'Coord 1', 'funcao' => 'coordenador']));
        $inativo = $svc->criar($orgao->id, EquipeDTO::fromRequest(['nome' => 'Coord 2', 'funcao' => 'coordenador', 'ativo' => false]));

        // Editar o coordenador inativo sem enviar `ativo` nao o reativa: nao conflita.
        $svc->atualizar($orgao->id, $inativo->id, EquipeDTO::fromRequest(['nome' => 'Coord 2b', 'funcao' => 'coordenador']));
        $this->assertFalse((bool) $inativo->fresh()->ativo);

        // Um segundo coordenador ativo (default do create) continua barrado.
        $this->expectException(InvalidArgumentException::class);
        $svc->criar($orgao->id, EquipeDTO::fromRequest(['nome' => 'Coord 3', 'funcao' => 'coordenador']));
    }

    public function test_orgao_preserva_tem_qtd_e_legacy_id_quando_ausentes(): void
    {
        $orgao = Orgao::factory()->create([
            'tem_viatura' => true,
            'tem_simulado' => true,
            'qtd_efetivo' => 12,
            'legacy_id' => 555,
        ]);

        app(OrgaoService::class)->atualizarOrgao($orgao->id, OrgaoDTO::fromRequest([
            'codigo' => $orgao->codigo,
            'nome'   => 'Novo nome',
            'tipo'   => $orgao->tipo instanceof \BackedEnum ? $orgao->tipo->value : $orgao->tipo,
            'status' => 'ativo',
            'tem_simulado' => false,
        ]));

        $orgao->refresh();
        $this->assertSame('Novo nome', $orgao->nome);
        $this->assertTrue((bool) $orgao->tem_viatura, 'tem_* ausente preservado');
        $this->assertFalse((bool) $orgao->tem_simulado, 'false explicito grava');
        $this->assertSame(12, (int) $orgao->qtd_efetivo, 'qtd ausente nao vira 0');
        $this->assertSame(555, (int) $orgao->legacy_id);
    }

    // ── Cisterna ────────────────────────────────────────────────────────────

    public function test_comunidade_inativa_e_legacy_id_preservados(): void
    {
        $comunidade = CisternaComunidade::factory()->create([
            'municipio_id' => \App\Models\Municipio::factory()->create()->id,
            'ativa'        => false,
            'legacy_id'    => 321,
        ]);

        app(ComunidadeService::class)->atualizar($comunidade, ComunidadeDTO::deValidados([
            'municipio_id' => $comunidade->municipio_id,
            'nome'         => 'Comunidade renomeada',
        ]));

        $comunidade->refresh();
        $this->assertSame('Comunidade renomeada', $comunidade->nome);
        $this->assertFalse((bool) $comunidade->ativa);
        $this->assertSame(321, (int) $comunidade->legacy_id);
    }

    public function test_ordem_de_servico_grava_documento_url(): void
    {
        $lote = CisternaLote::factory()->create();
        $svc = app(OrdemServicoService::class);

        $os = $svc->criar(OrdemServicoDTO::deValidados([
            'lote_id' => $lote->id, 'nome' => 'OS 1', 'documento_url' => 'https://sei.mg.gov.br/doc/1',
        ]));
        $this->assertSame('https://sei.mg.gov.br/doc/1', $os->fresh()->documento_url);

        $svc->atualizar($os, OrdemServicoDTO::deValidados([
            'lote_id' => $lote->id, 'nome' => 'OS 1', 'documento_url' => 'https://sei.mg.gov.br/doc/2',
        ]));
        $this->assertSame('https://sei.mg.gov.br/doc/2', $os->fresh()->documento_url);
    }

    // ── Tdap ────────────────────────────────────────────────────────────────

    public function test_prestador_inativo_nao_e_reativado_na_edicao(): void
    {
        $svc = app(PrestadorService::class);
        $dados = ['nome' => 'Transportes Teste', 'cnpj' => '11222333000181'];

        $prestador = $svc->criar(PrestadorDTO::fromRequest($dados + ['ativo' => false]));
        $svc->atualizar($prestador->id, PrestadorDTO::fromRequest(['nome' => 'Transportes Teste Ltda'] + $dados));

        $prestador->refresh();
        $this->assertSame('Transportes Teste Ltda', $prestador->nome);
        $this->assertFalse((bool) $prestador->ativo);
    }

    public function test_ata_criada_sem_ativo_nasce_ativa_e_inativa_e_preservada(): void
    {
        $svc = app(AtaService::class);
        $dados = ['numero' => 'ATA-T-' . random_int(1000, 9999), 'dt_inicio' => '2026-01-01', 'dt_final' => '2026-12-31'];

        $this->assertTrue((bool) $svc->criar(AtaDTO::fromRequest($dados))->fresh()->ativo, 'default da coluna no create');

        $inativa = $svc->criar(AtaDTO::fromRequest(['numero' => $dados['numero'] . '-B', 'ativo' => false] + $dados));
        $svc->atualizar($inativa->id, AtaDTO::fromRequest(['numero' => $dados['numero'] . '-B', 'observacoes' => 'ajuste'] + $dados));

        $this->assertFalse((bool) $inativa->fresh()->ativo);
    }
}
