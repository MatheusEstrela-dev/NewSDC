<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\DTOs;

final readonly class EstacaoCemadenDTO
{
    public function __construct(
        public string $codigo,
        public string $nome,
        public string $municipio,
        public string $uf,
        public float $latitude,
        public float $longitude,
        public ?int $codigoIbge = null,
        public ?int $idExterno = null,
        public string $tipo = 'Pluviométrica',
        public ?string $rede = null,
    ) {
    }

    /**
     * Converte um registro do feed. Devolve null quando falta o essencial para
     * plotar ou identificar.
     *
     * Diferente do INMET, aqui municipio e codigo IBGE vem prontos no feed:
     * verificado em 2026-09-02, as 830 estacoes de MG tinham codibge e
     * coordenada preenchidos, sem excecao. Isso dispensa a resolucao por
     * centroide mais proximo que o InmetRepository e obrigado a fazer.
     *
     * @param array<string, mixed> $dados
     */
    public static function fromFeedArray(array $dados): ?self
    {
        $lat = self::numero($dados['latitude'] ?? null);
        $lon = self::numero($dados['longitude'] ?? null);
        $codigo = trim((string) ($dados['codestacao'] ?? ''));

        // Lat 0 / lon 0 e o Golfo da Guine: ponto errado em mapa de Defesa
        // Civil tem consequencia operacional.
        if ($codigo === '' || $lat === null || $lon === null || $lat === 0.0 || $lon === 0.0) {
            return null;
        }

        $municipio = trim((string) ($dados['cidade'] ?? ''));
        $nome = trim((string) ($dados['nomeestacao'] ?? ''));

        return new self(
            codigo: $codigo,
            // A coluna nome e NOT NULL; estacao sem nome cai para o municipio,
            // que o feed sempre traz.
            nome: $nome !== '' ? $nome : $municipio,
            municipio: $municipio !== '' ? $municipio : $nome,
            uf: strtoupper((string) ($dados['uf'] ?? '')),
            latitude: $lat,
            longitude: $lon,
            codigoIbge: self::inteiro($dados['codibge'] ?? null),
            idExterno: self::inteiro($dados['idestacao'] ?? null),
            tipo: (string) ($dados['tipoestacao'] ?? 'Pluviométrica'),
            rede: isset($dados['nomerede']) ? (string) $dados['nomerede'] : null,
        );
    }

    private static function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) $valor;
    }

    private static function inteiro(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }
}
