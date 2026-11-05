<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Decretacoes;

use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use App\Modules\Decretacoes\Enums\StatusProcesso;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Requests\ReceiveProcessoRequest;
use App\Modules\Decretacoes\Requests\StoreProcessoRequest;
use App\Modules\Decretacoes\Requests\UpdateProcessoRequest;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * ProcessoRequestDTO (Decretacoes) sem HTTP: cada campo do formulario chega a
 * coluna certa do Processo, com o tipo certo, e toda chave que o DTO le tem
 * regra nas tres Requests que o alimentam (validated() descarta o resto).
 *
 * Os cenarios de update parcial de municipio/prazo ja estao em
 * tests/Unit/Dto/ContratoUpdateDosDtosTest.php e nao sao repetidos aqui.
 *
 * Estende Tests\TestCase porque as regras de `redec_id` leem o catalogo
 * dec_redecs (somente leitura, nada e gravado).
 */
class ProcessoRequestDTOMapeamentoTest extends TestCase
{
    /** Chaves que o DTO le fora do MAPA (origem, COBRADE, prazo, municipio, decretos). */
    private const CHAVES_FORA_DO_MAPA = [
        'origem',
        'cobrade_id',
        'tipo_desastre_id',
        'prazo_vigencia_decreto',
        'municipio_id',
        'informacoes_decreto',
    ];

    /** @return array<string, mixed> payload completo, como validated() o entrega */
    private function payloadCompleto(): array
    {
        return [
            'data_entrada'                      => '2026-03-01',
            'origem'                            => 'estadual',
            'municipio_id'                      => '17',
            'cobrade_id'                        => '12',
            'tipo_desastre_id'                  => '99',
            'situacao_anormalidade'             => 'ECP',
            'data_ocorrencia'                   => '2026-02-20',
            'status'                            => StatusProcesso::EM_ANALISE_ESTADO->value,
            'analista_id'                       => 'Analista Teste',
            'n_protocolo_fide'                  => 'MG-F-3106200-1-20260301',
            'redec_id'                          => 3,
            'n_decreto_municipal'               => '45/2026',
            'data_decreto_municipal'            => '2026-02-21',
            'data_publicacao_decreto_municipal' => '2026-02-22',
            'prazo_vigencia_decreto'            => '90',
            'n_decreto_estadual'                => '48.000',
            'data_decreto_estadual'             => '2026-02-25',
            'n_edicao_domg'                     => '40',
            'data_publicacao_domg'              => '2026-02-26',
            'n_portaria_federal'                => 'Portaria 1.234',
            'data_portaria_federal'             => '2026-03-02',
            'n_edicao_dou'                      => '41',
            'data_publicacao_dou'               => '2026-03-03',
            'n_processo_sei'                    => '1234.01.0000001/2026-01',
            'observacoes'                       => 'Observacao do analista',
            'informacoes_decreto'               => '[{"id":1,"observacao":"Desalojados na zona rural"}]',
        ];
    }

    /** @return array<string, mixed> colunas esperadas para o payloadCompleto() */
    private function colunasEsperadas(): array
    {
        return [
            'data_entrada'                => '2026-03-01',
            'tipo_desastre'               => 'ECP',
            'data_ocorrencia_desastre'    => '2026-02-20',
            'redec_id'                    => 3,
            'status'                      => StatusProcesso::EM_ANALISE_ESTADO->value,
            'analista'                    => 'Analista Teste',
            'n_protocolo_fide'            => 'MG-F-3106200-1-20260301',
            'decreto_municipal'           => '45/2026',
            'data_decreto_municipal'      => '2026-02-21',
            'data_publicacao_mg'          => '2026-02-22',
            'n_decreto_estadual'          => '48.000',
            'data_decreto_estadual'       => '2026-02-25',
            'n_edicao_domg'               => '40',
            'data_publicacao_domg'        => '2026-02-26',
            'portaria_reconhecimento_fed' => 'Portaria 1.234',
            'data_portaria_federal'       => '2026-03-02',
            'n_edicao_dou'                => '41',
            'data_publicacao_dou'         => '2026-03-03',
            'processo_inserido_sei'       => '1234.01.0000001/2026-01',
            'observacoes'                 => 'Observacao do analista',
            'processo'                    => 'ESTADUAL',
            'tipo_desastre_id'            => 12,
            'prazo_vigencia'              => 90,
        ];
    }

    /** @return list<string> */
    private function chavesLidasPeloDto(): array
    {
        $mapa = (new ReflectionClassConstant(ProcessoRequestDTO::class, 'MAPA'))->getValue();

        return array_values(array_unique(array_merge(array_keys($mapa), self::CHAVES_FORA_DO_MAPA)));
    }

    // ── Mapeamento ─────────────────────────────────────────────────────────

    public function test_create_mapeia_cada_campo_do_formulario_para_a_coluna_do_processo(): void
    {
        $dto = ProcessoRequestDTO::fromValidated($this->payloadCompleto());

        $esperado = $this->colunasEsperadas();
        $obtido = $dto->allData;
        ksort($esperado);
        ksort($obtido);

        $this->assertSame($esperado, $obtido);
        $this->assertSame([17], $dto->municipios, 'municipio_id vira lista de inteiros');
        $this->assertSame('[{"id":1,"observacao":"Desalojados na zona rural"}]', $dto->informacoesDecreto);
    }

    public function test_update_completo_gera_as_mesmas_colunas_do_create(): void
    {
        $dto = ProcessoRequestDTO::fromValidated($this->payloadCompleto(), parcial: true);

        $esperado = $this->colunasEsperadas();
        $obtido = $dto->allData;
        ksort($esperado);
        ksort($obtido);

        $this->assertSame($esperado, $obtido);
        $this->assertSame([17], $dto->municipios);
    }

    public function test_colunas_do_dto_estao_todas_no_fillable_do_processo(): void
    {
        $fillable = (new Processo())->getFillable();

        foreach ([false, true] as $parcial) {
            $colunas = array_keys(ProcessoRequestDTO::fromValidated($this->payloadCompleto(), $parcial)->allData);

            $this->assertSame(
                [],
                array_values(array_diff($colunas, $fillable)),
                'coluna fora do $fillable seria descartada em silencio pelo create()/fill()'
            );
        }
    }

    // ── Presenca e vazios ──────────────────────────────────────────────────

    public function test_create_descarta_vazios_e_update_limpa_o_campo_enviado_vazio(): void
    {
        $base = ['data_entrada' => '2026-03-01', 'origem' => 'municipal', 'municipio_id' => 5];

        $create = ProcessoRequestDTO::fromValidated($base + ['n_decreto_municipal' => '', 'observacoes' => null]);
        $this->assertArrayNotHasKey('decreto_municipal', $create->allData, 'create nao grava nulos');
        $this->assertArrayNotHasKey('observacoes', $create->allData);

        $update = ProcessoRequestDTO::fromValidated(['n_decreto_municipal' => '', 'observacoes' => null], parcial: true);
        $this->assertArrayHasKey('decreto_municipal', $update->allData);
        $this->assertNull($update->allData['decreto_municipal'], "'' enviado no update limpa a coluna");
        $this->assertArrayHasKey('observacoes', $update->allData);
        $this->assertNull($update->allData['observacoes']);
    }

    public function test_update_sem_origem_nao_toca_a_coluna_processo_e_com_origem_grava_em_maiusculas(): void
    {
        $sem = ProcessoRequestDTO::fromValidated(['observacoes' => 'x'], parcial: true);
        $this->assertArrayNotHasKey('processo', $sem->allData, 'ausente no update nao pode virar string vazia');
        $this->assertArrayNotHasKey('status', $sem->allData, 'update nao inventa status');
        $this->assertArrayNotHasKey('tipo_desastre_id', $sem->allData);

        $com = ProcessoRequestDTO::fromValidated(['origem' => 'municipal'], parcial: true);
        $this->assertSame('MUNICIPAL', $com->allData['processo']);
    }

    public function test_prazo_enviado_em_branco_grava_o_padrao_legal_de_180_dias(): void
    {
        // Intencional (comentario em fromValidated): prazo em branco = padrao SE/ECP.
        $dto = ProcessoRequestDTO::fromValidated(['prazo_vigencia_decreto' => null], parcial: true);

        $this->assertSame(180, $dto->allData['prazo_vigencia']);
    }

    // ── Conversao numerica ─────────────────────────────────────────────────

    public function test_cobrade_tem_precedencia_e_texto_invalido_nunca_vira_zero(): void
    {
        $this->assertSame(
            7,
            ProcessoRequestDTO::fromValidated(['cobrade_id' => 'abc', 'tipo_desastre_id' => '7'], parcial: true)->allData['tipo_desastre_id'],
            'cobrade invalido cai para o tipo_desastre_id'
        );

        $invalido = ProcessoRequestDTO::fromValidated(['cobrade_id' => 'abc', 'tipo_desastre_id' => '12,5'], parcial: true);
        $this->assertArrayHasKey('tipo_desastre_id', $invalido->allData);
        $this->assertNull($invalido->allData['tipo_desastre_id'], 'texto numerico invalido vira null, nunca 0');

        $limpo = ProcessoRequestDTO::fromValidated(['cobrade_id' => ''], parcial: true);
        $this->assertNull($limpo->allData['tipo_desastre_id'], 'cobrade enviado vazio limpa o tipo');

        $this->assertArrayNotHasKey(
            'tipo_desastre_id',
            ProcessoRequestDTO::fromValidated(['data_entrada' => '2026-01-01', 'origem' => 'municipal', 'municipio_id' => 1, 'cobrade_id' => null])->allData,
            'no create o tipo nulo fica de fora (default do banco)'
        );
    }

    public function test_municipio_invalido_vira_lista_vazia_e_nunca_municipio_zero(): void
    {
        $dto = ProcessoRequestDTO::fromValidated(['municipio_id' => 'abc'], parcial: true);

        $this->assertSame([], $dto->municipios);
    }

    // ── Cobertura das Requests ─────────────────────────────────────────────

    public function test_store_update_e_receive_tem_regra_para_toda_chave_que_o_dto_le(): void
    {
        $chaves = $this->chavesLidasPeloDto();
        $this->assertCount(26, $chaves, 'MAPA (20) + 6 chaves tratadas a parte');

        foreach ([StoreProcessoRequest::class, UpdateProcessoRequest::class, ReceiveProcessoRequest::class] as $classe) {
            $regras = array_keys((new $classe())->rules());

            $this->assertSame(
                [],
                array_values(array_diff($chaves, $regras)),
                "{$classe}: chave lida pelo DTO sem regra e descartada pelo validated()"
            );
        }
    }

    public function test_requests_nao_aceitam_colunas_internas_do_processo(): void
    {
        $internas = ['legacy_id', 'created_by', 'cobrade', 'reconhecimento', 'situacao_processo', 'processo', 'prazo_vigencia', 'orgao_responsavel_id'];

        foreach ([StoreProcessoRequest::class, UpdateProcessoRequest::class, ReceiveProcessoRequest::class] as $classe) {
            $regras = array_keys((new $classe())->rules());

            $this->assertSame([], array_values(array_intersect($internas, $regras)), "{$classe} nao pode aceitar coluna interna");
        }
    }
}
