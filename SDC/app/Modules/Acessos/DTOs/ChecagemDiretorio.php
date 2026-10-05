<?php

declare(strict_types=1);

namespace App\Modules\Acessos\DTOs;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;

/**
 * Resultado de uma checagem do diagnostico do diretorio. O detalhe e sempre
 * um texto fixo ou o codigo de CodigoErroDiretorio: nunca segredo nem a
 * mensagem crua do servidor LDAP.
 */
final readonly class ChecagemDiretorio
{
    public const OK = 'ok';

    public const FALHA = 'falha';

    public const IGNORADO = 'ignorado';

    private function __construct(
        public string $nome,
        public string $estado,
        public string $detalhe,
    ) {}

    public static function ok(string $nome, string $detalhe = ''): self
    {
        return new self($nome, self::OK, $detalhe);
    }

    public static function falha(string $nome, string $detalhe): self
    {
        return new self($nome, self::FALHA, $detalhe);
    }

    public static function falhaComCodigo(string $nome, CodigoErroDiretorio $codigo): self
    {
        return new self($nome, self::FALHA, $codigo->value);
    }

    public static function ignorado(string $nome, string $detalhe): self
    {
        return new self($nome, self::IGNORADO, $detalhe);
    }

    public function passou(): bool
    {
        return $this->estado !== self::FALHA;
    }

    /** @return array{checagem: string, estado: string, detalhe: string} */
    public function toArray(): array
    {
        return ['checagem' => $this->nome, 'estado' => $this->estado, 'detalhe' => $this->detalhe];
    }
}
