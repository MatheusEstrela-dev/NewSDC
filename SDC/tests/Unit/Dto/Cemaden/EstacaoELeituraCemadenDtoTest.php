<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\Cemaden;

use App\Modules\Cemaden\DTOs\EstacaoCemadenDTO;
use App\Modules\Cemaden\DTOs\LeituraCemadenDTO;
use App\Modules\Cemaden\Normalizadores\CemadenJsonNormalizador;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * DTOs de integracao do CEMADEN (plano_dtos, P1/P2 "Integracoes"): o registro
 * do feed vira EstacaoCemadenDTO/LeituraCemadenDTO sem transformar texto
 * invalido em medicao zero e sem plotar estacao sem coordenada.
 *
 * Os casos "n/d", "12,5" e "0" de acumulado ja estao em
 * tests/Feature/Dto/AuditoriaDtoFluxoEscritaTest; aqui entram os demais
 * formatos do feed e o caminho pelo normalizador.
 */
class EstacaoELeituraCemadenDtoTest extends TestCase
{
    /** Registro no formato do feed 311_24 (campos minusculos, numeros JSON). */
    private function registro(array $sobrescrever = []): array
    {
        return array_merge([
            'idestacao' => 7139,
            'codestacao' => '310620601A',
            'cidade' => 'BELO HORIZONTE',
            'nomeestacao' => 'Barreiro',
            'uf' => 'mg',
            'codibge' => 3106200,
            'latitude' => -19.976,
            'longitude' => -44.02,
            'tipoestacao' => 'Pluviométrica',
            'nomerede' => 'Rede Cemaden',
            'acumulado' => 12.4,
        ], $sobrescrever);
    }

    // ── EstacaoCemadenDTO ───────────────────────────────────────────────────

    public function test_estacao_completa_mapeia_todos_os_campos_com_tipo(): void
    {
        $dto = EstacaoCemadenDTO::fromFeedArray($this->registro(['codestacao' => '  310620601A  ']));

        $this->assertNotNull($dto);
        $this->assertSame('310620601A', $dto->codigo, 'codigo sai sem espacos');
        $this->assertSame('Barreiro', $dto->nome);
        $this->assertSame('BELO HORIZONTE', $dto->municipio);
        $this->assertSame('MG', $dto->uf, 'UF normalizada em maiusculas');
        $this->assertSame(-19.976, $dto->latitude);
        $this->assertSame(-44.02, $dto->longitude);
        $this->assertSame(3106200, $dto->codigoIbge);
        $this->assertSame(7139, $dto->idExterno);
        $this->assertSame('Pluviométrica', $dto->tipo);
        $this->assertSame('Rede Cemaden', $dto->rede);
    }

    public function test_estacao_aceita_numeros_em_texto_e_virgula_decimal(): void
    {
        $dto = EstacaoCemadenDTO::fromFeedArray($this->registro([
            'latitude' => '-19,976',
            'longitude' => ' -44.02 ',
            'codibge' => '3106200',
            'idestacao' => '7139',
        ]));

        $this->assertNotNull($dto);
        $this->assertSame(-19.976, $dto->latitude);
        $this->assertSame(-44.02, $dto->longitude);
        $this->assertSame(3106200, $dto->codigoIbge);
        $this->assertSame(7139, $dto->idExterno);
    }

    public static function coordenadasInutilizaveis(): array
    {
        return [
            'sem latitude' => [['latitude' => null]],
            'sem longitude' => [['longitude' => null]],
            'latitude vazia' => [['latitude' => '']],
            'latitude n/d' => [['latitude' => 'n/d']],
            'longitude com traco' => [['longitude' => '-']],
            'latitude zero (Golfo da Guine)' => [['latitude' => 0]],
            'longitude zero em texto' => [['longitude' => '0,0']],
            'latitude booleana' => [['latitude' => true]],
        ];
    }

    #[DataProvider('coordenadasInutilizaveis')]
    public function test_estacao_sem_coordenada_utilizavel_e_rejeitada(array $sobrescrever): void
    {
        $this->assertNull(EstacaoCemadenDTO::fromFeedArray($this->registro($sobrescrever)));
    }

    public function test_estacao_sem_chave_de_coordenada_e_rejeitada(): void
    {
        $registro = $this->registro();
        unset($registro['latitude'], $registro['longitude']);

        $this->assertNull(EstacaoCemadenDTO::fromFeedArray($registro));
    }

    public function test_estacao_sem_codigo_e_rejeitada(): void
    {
        $this->assertNull(EstacaoCemadenDTO::fromFeedArray($this->registro(['codestacao' => '   '])));
        $this->assertNull(EstacaoCemadenDTO::fromFeedArray($this->registro(['codestacao' => null])));
    }

    public function test_codigo_ibge_e_id_externo_invalidos_viram_ausencia_e_nao_zero(): void
    {
        $dto = EstacaoCemadenDTO::fromFeedArray($this->registro(['codibge' => 'n/d', 'idestacao' => '12abc']));

        $this->assertNotNull($dto);
        $this->assertNull($dto->codigoIbge);
        $this->assertNull($dto->idExterno);
    }

    public function test_nome_e_municipio_se_completam_porque_as_colunas_sao_not_null(): void
    {
        $semNome = EstacaoCemadenDTO::fromFeedArray($this->registro(['nomeestacao' => '']));
        $semCidade = EstacaoCemadenDTO::fromFeedArray($this->registro(['cidade' => ' ']));

        $this->assertSame('BELO HORIZONTE', $semNome?->nome);
        $this->assertSame('BELO HORIZONTE', $semNome?->municipio);
        $this->assertSame('Barreiro', $semCidade?->municipio);
    }

    public function test_tipo_e_rede_ausentes_usam_padrao_e_nulo(): void
    {
        $registro = $this->registro();
        unset($registro['tipoestacao'], $registro['nomerede']);

        $dto = EstacaoCemadenDTO::fromFeedArray($registro);

        $this->assertSame('Pluviométrica', $dto?->tipo);
        $this->assertNull($dto?->rede);
    }

    // ── LeituraCemadenDTO ───────────────────────────────────────────────────

    public static function acumulados(): array
    {
        return [
            'numero JSON zero' => [0, 0.0],
            'float JSON zero' => [0.0, 0.0],
            'float JSON' => [3.4, 3.4],
            'inteiro JSON' => [15, 15.0],
            'texto com milhar e virgula' => ['1.234,5', 1234.5],
            'null (nao transmitiu)' => [null, null],
            'texto vazio' => ['', null],
            'traco' => ['-', null],
            'texto qualquer' => ['sem dado', null],
            'booleano' => [false, null],
        ];
    }

    #[DataProvider('acumulados')]
    public function test_acumulado_distingue_zero_de_ausencia(mixed $bruto, ?float $esperado): void
    {
        $dto = LeituraCemadenDTO::fromFeedArray(['codestacao' => 'X1', 'acumulado' => $bruto], Carbon::now());

        $this->assertNotNull($dto);
        $this->assertSame($esperado, $dto->acumulado24h);
    }

    public function test_leitura_sem_chave_acumulado_e_ausencia(): void
    {
        $this->assertNull(LeituraCemadenDTO::fromFeedArray(['codestacao' => 'X1'], Carbon::now())?->acumulado24h);
    }

    public function test_leitura_sem_codigo_e_rejeitada_e_horario_vem_do_bloco(): void
    {
        $instante = Carbon::parse('2026-09-02 19:26:40', 'UTC');

        $this->assertNull(LeituraCemadenDTO::fromFeedArray(['codestacao' => ' ', 'acumulado' => 1], $instante));

        $dto = LeituraCemadenDTO::fromFeedArray(['codestacao' => ' 310620601A ', 'acumulado' => 1], $instante);
        $this->assertSame('310620601A', $dto?->codigoEstacao);
        $this->assertSame($instante, $dto?->medidoEm, 'o instante global do snapshot e o da leitura');
    }

    // ── Normalizador (funcao pura sobre o PayloadBruto) ─────────────────────

    private function payload(array $estacoes, ?string $atualizado = '2026-09-02 19:26:40 UTC'): PayloadBruto
    {
        return new PayloadBruto(
            (string) json_encode(['atualizado' => $atualizado, 'estacoes' => $estacoes], JSON_UNESCAPED_UNICODE),
            'cemaden-json',
        );
    }

    public function test_normalizador_entrega_dimensao_antes_do_fato_com_instante_em_utc(): void
    {
        $dtos = iterator_to_array((new CemadenJsonNormalizador())->normalizar($this->payload([
            $this->registro(),
            $this->registro(['codestacao' => '310620602A', 'nomeestacao' => 'Pampulha', 'acumulado' => null]),
        ])), false);

        $this->assertCount(4, $dtos);
        $this->assertInstanceOf(EstacaoCemadenDTO::class, $dtos[0]);
        $this->assertInstanceOf(EstacaoCemadenDTO::class, $dtos[1]);
        $this->assertInstanceOf(LeituraCemadenDTO::class, $dtos[2]);
        $this->assertInstanceOf(LeituraCemadenDTO::class, $dtos[3]);

        $this->assertSame('310620601A', $dtos[2]->codigoEstacao);
        $this->assertSame(12.4, $dtos[2]->acumulado24h);
        $this->assertNull($dtos[3]->acumulado24h, 'estacao sem telemetria fica sem leitura, nao com chuva zero');

        // "2026-09-02 19:26:40 UTC" e UTC explicito: errar o fuso deslocaria a serie em 3h.
        $this->assertSame('2026-09-02T19:26:40+00:00', $dtos[2]->medidoEm->toIso8601String());
    }

    public function test_normalizador_descarta_estacao_sem_coordenada_e_registro_que_nao_e_objeto(): void
    {
        $dtos = iterator_to_array((new CemadenJsonNormalizador())->normalizar($this->payload([
            $this->registro(['latitude' => 'n/d']),
            'lixo',
            $this->registro(['codestacao' => 'OK1']),
        ])), false);

        $estacoes = array_values(array_filter($dtos, fn ($d) => $d instanceof EstacaoCemadenDTO));

        $this->assertCount(1, $estacoes);
        $this->assertSame('OK1', $estacoes[0]->codigo);
    }

    public function test_normalizador_sem_instante_global_entrega_so_a_dimensao(): void
    {
        foreach ([null, '', 'ontem a tarde'] as $atualizado) {
            $dtos = iterator_to_array((new CemadenJsonNormalizador())->normalizar(
                $this->payload([$this->registro()], $atualizado)
            ), false);

            $this->assertCount(1, $dtos, 'sem "atualizado" valido nao se inventa a hora da medicao');
            $this->assertInstanceOf(EstacaoCemadenDTO::class, $dtos[0]);
        }
    }

    public function test_normalizador_ignora_conteudo_vazio_ou_que_nao_e_json(): void
    {
        $normalizador = new CemadenJsonNormalizador();

        $this->assertSame([], iterator_to_array($normalizador->normalizar(new PayloadBruto('', 'cemaden-json')), false));
        $this->assertSame([], iterator_to_array($normalizador->normalizar(new PayloadBruto('estacoes([', 'cemaden-json')), false));
    }
}
