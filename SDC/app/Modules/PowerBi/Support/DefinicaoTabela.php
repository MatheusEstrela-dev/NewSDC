<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use InvalidArgumentException;

/**
 * Uma tabela bi_* do catalogo (config/powerbi.php). Identificadores sao
 * validados porque entram no SQL sem bind.
 */
final class DefinicaoTabela
{
    public const INCREMENTAL = 'incremental';

    public const COMPLETA = 'completa';

    /**
     * @param list<string> $pk
     * @param list<string> $colunas  dados, sem colunas de controle
     * @param list<string> $filtros
     * @param list<string> $expostas
     */
    public function __construct(
        public readonly string $modulo,
        public readonly string $nome,
        public readonly string $schema,
        public readonly array $pk,
        public readonly array $colunas,
        public readonly string $estrategia = self::INCREMENTAL,
        public readonly array $filtros = [],
        public readonly array $expostas = [],
        public readonly ?string $colunaPai = null,
    ) {
        $ids = [$nome, $schema, ...$pk, ...$colunas, ...$filtros, ...$expostas];
        if ($colunaPai !== null) {
            $ids[] = $colunaPai;
        }
        foreach ($ids as $id) {
            if (preg_match('/^[a-z_][a-z0-9_]*$/', $id) !== 1) {
                throw new InvalidArgumentException("Identificador invalido no catalogo de BI: {$id}");
            }
        }
        if ($pk === [] || array_diff($pk, $colunas) !== []) {
            throw new InvalidArgumentException("PK de {$schema}.{$nome} vazia ou fora de colunas.");
        }
        if (! in_array($estrategia, [self::INCREMENTAL, self::COMPLETA], true)) {
            throw new InvalidArgumentException("Estrategia invalida: {$estrategia}");
        }
    }

    /** @param array<string, mixed> $c */
    public static function dosConfig(string $modulo, string $nome, array $c): self
    {
        $colunas = array_values($c['colunas'] ?? []);

        return new self(
            $modulo,
            $nome,
            (string) ($c['schema'] ?? ''),
            array_values($c['pk'] ?? []),
            $colunas,
            (string) ($c['estrategia'] ?? self::INCREMENTAL),
            array_values($c['filtros'] ?? []),
            array_values($c['expostas'] ?? $colunas),
            isset($c['pai']['coluna']) ? (string) $c['pai']['coluna'] : null,
        );
    }

    public function alvo(): string
    {
        return "{$this->schema}.{$this->nome}";
    }

    public function ehFilha(): bool
    {
        return $this->colunaPai !== null;
    }

    public function completa(): bool
    {
        return $this->estrategia === self::COMPLETA;
    }

    /** Chave de linha (pk unida) para comparar fonte x destino. */
    public function chaveDe(array $linha): string
    {
        return implode('|', array_map(fn (string $c): string => (string) ($linha[$c] ?? ''), $this->pk));
    }
}
