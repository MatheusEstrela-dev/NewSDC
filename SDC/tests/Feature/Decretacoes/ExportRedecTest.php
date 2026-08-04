<?php

declare(strict_types=1);

namespace Tests\Feature\Decretacoes;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Decretacoes\Filters\ProcessoFilter;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Exportacao CSV das decretacoes por REDEC (GET decretacoes/export/redec).
 */
class ExportRedecTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSAO = 'decretacoes.processos.export';

    private const REDEC_A = 7;

    private const REDEC_B = 3;

    private Municipio $municipioA;

    private Municipio $municipioB;

    protected function setUp(): void
    {
        parent::setUp();

        // Processo::creating exige usuario autenticado para preencher created_by;
        // os cenarios sao montados antes de actingAsExportador().
        $this->actingAs(User::factory()->create());

        [$this->municipioA, $this->municipioB] = Municipio::query()
            ->whereNotNull('codigo_ibge')
            ->whereRaw('length(codigo_ibge) = 7')
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();

        ProcessoFilter::clearCache();
    }

    private function actingAsExportador(): static
    {
        Permission::firstOrCreate(['name' => self::PERMISSAO, 'guard_name' => 'web']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->givePermissionTo(self::PERMISSAO);

        return $this->actingAs($user);
    }

    private function criaProcesso(int $redecId, string $protocolo): Processo
    {
        return Processo::create([
            'data_entrada'           => '2026-02-10',
            'processo'               => 'MUNICIPAL',
            'status'                 => 'Reconhecido pelo Estado',
            'redec_id'               => $redecId,
            'n_protocolo_fide'       => $protocolo,
            'decreto_municipal'      => '1234',
            'data_decreto_municipal' => '2026-02-01',
            'data_publicacao_mg'     => '2026-02-05',
            'prazo_vigencia'         => 180,
            'analista'               => 'Analista Teste',
        ]);
    }

    /**
     * @return array{0: Processo, 1: Processo}
     */
    private function cenarioDuasRedecs(): array
    {
        $pA = $this->criaProcesso(self::REDEC_A, 'MG-F-' . $this->municipioA->codigo_ibge . '-11111-20260210');
        DecretoMunicipio::create([
            'entrada_processos_id' => $pA->id,
            // Vinculo legado: resolvido pelo IBGE do protocolo.
            'municipio_id'         => 999999,
            'n_protocolo_fide'     => 'MG-F-' . $this->municipioA->codigo_ibge . '-11111-20260210',
        ]);

        $pB = $this->criaProcesso(self::REDEC_B, 'MG-F-' . $this->municipioB->codigo_ibge . '-22222-20260210');
        DecretoMunicipio::create([
            'entrada_processos_id' => $pB->id,
            // Vinculo do formulario atual: municipio_id = municipios.id.
            'municipio_id'         => $this->municipioB->id,
            'n_protocolo_fide'     => null,
        ]);

        ProcessoFilter::clearCache();

        return [$pA, $pB];
    }

    /** @return array<int, array<int, string>> linhas do CSV, ja separadas por `;` */
    private function baixaCsv(array $params): array
    {
        $resposta = $this->actingAsExportador()->get(route('decretacoes.export.redec', $params));

        $resposta->assertOk();
        $resposta->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // O BOM UTF-8 e proposital (Excel), mas atrapalha a comparacao.
        $conteudo = ltrim($resposta->streamedContent(), "\u{FEFF}");

        // str_getcsv (e nao explode): fputcsv envolve em aspas todo campo com
        // espaco, entao "7ª REDEC" chega como '"7ª REDEC"'.
        return array_values(array_map(
            fn (string $linha) => str_getcsv($linha, ';', '"', '\\'),
            array_filter(preg_split('/\r?\n/', $conteudo) ?: [], fn ($l) => trim((string) $l) !== '')
        ));
    }

    public function test_exige_permissao_de_exportacao(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('decretacoes.export.redec'))
            ->assertForbidden();
    }

    public function test_cabecalho_sai_mesmo_sem_nenhuma_linha(): void
    {
        // REDEC sem nenhuma decretacao no recorte.
        $linhas = $this->baixaCsv(['redec_id' => 14, 'data_inicio' => '1900-01-01', 'data_fim' => '1900-01-02']);

        $this->assertCount(1, $linhas, 'Deveria sair apenas o cabecalho.');
        $this->assertSame('redec_id', $linhas[0][0]);
        $this->assertContains('redec', $linhas[0]);
        $this->assertContains('municipio', $linhas[0]);
        $this->assertContains('situacao_vigencia', $linhas[0]);
    }

    public function test_exporta_apenas_a_redec_escolhida(): void
    {
        [$pA, $pB] = $this->cenarioDuasRedecs();

        $linhas = $this->baixaCsv(['redec_id' => self::REDEC_A, 'all' => 1]);
        $cabecalho = array_shift($linhas);
        $colId = array_search('processo_id', $cabecalho, true);
        $ids = array_map(fn (array $l) => (int) $l[$colId], $linhas);

        $this->assertContains($pA->id, $ids);
        $this->assertNotContains($pB->id, $ids);
    }

    public function test_linha_traz_a_redec_e_o_municipio_resolvido_do_vinculo_legado(): void
    {
        [$pA] = $this->cenarioDuasRedecs();

        $linhas = $this->baixaCsv(['redec_id' => self::REDEC_A, 'all' => 1]);
        $cabecalho = array_shift($linhas);
        $indice = array_flip($cabecalho);

        $linha = collect($linhas)->firstWhere($indice['processo_id'], (string) $pA->id);

        $this->assertNotNull($linha, 'A decretacao da REDEC A deveria estar no CSV.');
        $this->assertSame((string) self::REDEC_A, $linha[$indice['redec_id']]);
        $this->assertSame('7ª REDEC', $linha[$indice['redec']]);
        // Municipio veio do IBGE do protocolo, nao do municipio_id (que e 999999).
        $this->assertSame($this->municipioA->nome, $linha[$indice['municipio']]);
        $this->assertSame($this->municipioA->codigo_ibge, $linha[$indice['codigo_ibge']]);
        $this->assertSame('180', $linha[$indice['prazo_vigencia_dias']]);
        $this->assertSame('2026-08-04', $linha[$indice['data_vencimento']]);
        $this->assertSame('Reconhecido pelo Estado', $linha[$indice['status']]);
    }

    public function test_sem_redec_escolhida_traz_todas_ordenadas_por_redec(): void
    {
        [$pA, $pB] = $this->cenarioDuasRedecs();

        $linhas = $this->baixaCsv(['all' => 1]);
        $cabecalho = array_shift($linhas);
        $indice = array_flip($cabecalho);

        $ids = array_map(fn (array $l) => (int) $l[$indice['processo_id']], $linhas);
        $this->assertContains($pA->id, $ids);
        $this->assertContains($pB->id, $ids);

        // REDEC B (3) tem que vir antes da REDEC A (7).
        $posicaoA = array_search($pA->id, $ids, true);
        $posicaoB = array_search($pB->id, $ids, true);
        $this->assertLessThan($posicaoA, $posicaoB, 'As linhas deveriam sair ordenadas por REDEC.');

        // E as REDECs, quando presentes, sao nao-decrescentes ao longo do arquivo.
        $redecs = array_values(array_filter(
            array_map(fn (array $l) => $l[$indice['redec_id']], $linhas),
            fn ($v) => $v !== ''
        ));
        $ordenadas = $redecs;
        sort($ordenadas, SORT_NUMERIC);
        $this->assertSame($ordenadas, $redecs);
    }

    public function test_periodo_recorta_pela_data_de_entrada(): void
    {
        [$pA] = $this->cenarioDuasRedecs();

        $dentro = $this->baixaCsv(['redec_id' => self::REDEC_A, 'type' => 'period', 'data_inicio' => '2026-02-01', 'data_fim' => '2026-02-28']);
        $fora   = $this->baixaCsv(['redec_id' => self::REDEC_A, 'type' => 'period', 'data_inicio' => '2026-03-01', 'data_fim' => '2026-03-31']);

        $idsDentro = array_map(fn (array $l) => (int) $l[6], array_slice($dentro, 1));
        $idsFora   = array_map(fn (array $l) => (int) $l[6], array_slice($fora, 1));

        $this->assertContains($pA->id, $idsDentro);
        $this->assertNotContains($pA->id, $idsFora);
    }
}
