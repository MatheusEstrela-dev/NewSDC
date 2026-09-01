<?php

declare(strict_types=1);

namespace App\Modules\Inmet\DTOs;

final readonly class EstacaoDTO
{
    public function __construct(
        public string $codigo,
        public string $nome,
        public string $uf,
        public float $latitude,
        public float $longitude,
        public ?float $altitude = null,
        public ?string $situacao = null,
        public ?string $tipo = null,
    ) {
    }

    /**
     * Converte um registro de /estacoes/T. Devolve null quando a estacao nao
     * tem coordenada utilizavel — o chamador descarta em vez de plotar em zero.
     *
     * @param array<string, mixed> $dados
     */
    public static function fromInventarioArray(array $dados): ?self
    {
        $lat = self::numero($dados['VL_LATITUDE'] ?? null);
        $lon = self::numero($dados['VL_LONGITUDE'] ?? null);

        // Lat 0 / lon 0 e o Golfo da Guine: nenhuma estacao do INMET fica la, e
        // ponto errado em mapa de Defesa Civil tem consequencia operacional.
        if ($lat === null || $lon === null || $lat === 0.0 || $lon === 0.0) {
            return null;
        }

        return new self(
            codigo: (string) ($dados['CD_ESTACAO'] ?? ''),
            nome: (string) ($dados['DC_NOME'] ?? ''),
            // O inventario usa SG_ESTADO; o payload de leituras usa UF.
            uf: (string) ($dados['SG_ESTADO'] ?? $dados['UF'] ?? ''),
            latitude: $lat,
            longitude: $lon,
            altitude: self::numero($dados['VL_ALTITUDE'] ?? null),
            situacao: isset($dados['CD_SITUACAO']) ? (string) $dados['CD_SITUACAO'] : null,
            tipo: isset($dados['TP_ESTACAO']) ? (string) $dados['TP_ESTACAO'] : null,
        );
    }

    /** A API devolve todo numero como string, e ausencia como null ou ''. */
    private static function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) $valor;
    }
}
