<?php

declare(strict_types=1);

namespace App\Modules\Acessos\DTOs;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Conta de usuario como o SDC a enxerga no AD. Lista fechada de campos
 * (spec 4.2): nenhum outro atributo do diretorio atravessa a porta.
 */
final readonly class ContaDiretorio
{
    public function __construct(
        public string $objectGuid,
        public string $login,
        public ?string $upn,
        public string $dn,
        public ?string $nomeExibicao,
        public ?string $email,
        public bool $ativa,
        public bool $bloqueada,
        public bool $trocaSenhaPendente,
        public bool $protegida,
        public ?DateTimeImmutable $alteradaEm = null,
    ) {}

    public function referencia(): ReferenciaConta
    {
        return new ReferenciaConta($this->objectGuid, $this->dn, $this->login, $this->nomeExibicao);
    }

    /**
     * Chaves iguais aos nomes das propriedades; `alteradaEm` aceita
     * DateTimeInterface ou texto de data.
     *
     * @param array<string, mixed> $dados
     */
    public static function fromArray(array $dados): self
    {
        $alteradaEm = $dados['alteradaEm'] ?? null;
        if ($alteradaEm instanceof DateTimeInterface) {
            $alteradaEm = DateTimeImmutable::createFromInterface($alteradaEm);
        } elseif (is_string($alteradaEm) && $alteradaEm !== '') {
            $alteradaEm = new DateTimeImmutable($alteradaEm);
        } else {
            $alteradaEm = null;
        }

        return new self(
            objectGuid: (string) $dados['objectGuid'],
            login: (string) $dados['login'],
            upn: self::textoOuNulo($dados['upn'] ?? null),
            dn: (string) $dados['dn'],
            nomeExibicao: self::textoOuNulo($dados['nomeExibicao'] ?? null),
            email: self::textoOuNulo($dados['email'] ?? null),
            ativa: (bool) ($dados['ativa'] ?? true),
            bloqueada: (bool) ($dados['bloqueada'] ?? false),
            trocaSenhaPendente: (bool) ($dados['trocaSenhaPendente'] ?? false),
            protegida: (bool) ($dados['protegida'] ?? false),
            alteradaEm: $alteradaEm,
        );
    }

    /** @return array<string, mixed> forma inversa de fromArray (data em ATOM) */
    public function toArray(): array
    {
        return [
            'objectGuid' => $this->objectGuid,
            'login' => $this->login,
            'upn' => $this->upn,
            'dn' => $this->dn,
            'nomeExibicao' => $this->nomeExibicao,
            'email' => $this->email,
            'ativa' => $this->ativa,
            'bloqueada' => $this->bloqueada,
            'trocaSenhaPendente' => $this->trocaSenhaPendente,
            'protegida' => $this->protegida,
            'alteradaEm' => $this->alteradaEm?->format(DATE_ATOM),
        ];
    }

    private static function textoOuNulo(mixed $valor): ?string
    {
        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
