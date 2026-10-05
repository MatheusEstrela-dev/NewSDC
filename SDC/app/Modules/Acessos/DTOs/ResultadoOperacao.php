<?php

declare(strict_types=1);

namespace App\Modules\Acessos\DTOs;

use JsonSerializable;
use LogicException;

/**
 * Resultado de uma escrita: a conta relida depois do modify e se o estado de
 * fato mudou. `senhaParaEntrega` so e preenchida pelo handler de reset e leva
 * a senha da memoria do handler ate o cofre; por isso o objeto nunca e
 * serializado (fila, cache, sessao) e a senha nao aparece em dump nem JSON.
 */
final readonly class ResultadoOperacao implements JsonSerializable
{
    public function __construct(
        public ContaDiretorio $contaDepois,
        public bool $efetivada,
        #[\SensitiveParameter] public ?string $senhaParaEntrega = null,
    ) {}

    public function comSenhaParaEntrega(#[\SensitiveParameter] string $senha): self
    {
        return new self($this->contaDepois, $this->efetivada, $senha);
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return $this->semSenha();
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->semSenha();
    }

    /** @return never */
    public function __serialize(): array
    {
        throw new LogicException('ResultadoOperacao nao pode ser serializado.');
    }

    /** @param array<string, mixed> $dados */
    public function __unserialize(array $dados): void
    {
        throw new LogicException('ResultadoOperacao nao pode ser desserializado.');
    }

    /** @return array<string, mixed> */
    private function semSenha(): array
    {
        return [
            'contaDepois' => $this->contaDepois,
            'efetivada' => $this->efetivada,
            'senhaParaEntrega' => $this->senhaParaEntrega === null ? null : '[omitida]',
        ];
    }
}
