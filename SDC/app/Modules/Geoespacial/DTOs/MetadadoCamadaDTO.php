<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\DTOs;

/**
 * Os metadados editaveis de uma camada.
 *
 * A GEOMETRIA nao esta aqui, e nao por esquecimento: ela e a identidade da
 * camada -- hash_arquivo e UNIQUE e calculado sobre o KML -- e o Bronze guarda
 * o arquivo que a originou. Editar geometria romperia a correspondencia entre o
 * que esta no mapa e o que o municipio enviou, que e a unica coisa auditavel
 * neste modulo. Corrigir area e enviar outro KML.
 */
final readonly class MetadadoCamadaDTO
{
    public function __construct(
        public string $nome,
        public string $dominio,
        public string $nivel,
        public ?string $emitidoEm,
        public ?string $validoAte,
    ) {
    }

    /** @param array<string, mixed> $dados */
    public static function fromRequest(array $dados): self
    {
        return new self(
            nome: (string) ($dados['nome'] ?? ''),
            dominio: (string) ($dados['dominio'] ?? ''),
            nivel: (string) ($dados['nivel'] ?? ''),
            // Datas normalizadas para Y-m-d aqui, e nao no Service: a coluna e
            // `date` e o navegador manda ISO com fuso quando o campo e
            // datetime-local. Deixar isso para o banco resolveria por sorte.
            emitidoEm: self::comoData($dados['emitido_em'] ?? null),
            validoAte: self::comoData($dados['valido_ate'] ?? null),
        );
    }

    private static function comoData(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $data = date_create((string) $valor);

        return $data === false ? null : $data->format('Y-m-d');
    }
}
