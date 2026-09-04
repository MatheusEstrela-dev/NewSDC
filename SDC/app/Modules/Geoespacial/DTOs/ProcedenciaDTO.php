<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\DTOs;

use LogicException;

/**
 * Resultado da resolucao de procedencia de um envio municipal.
 *
 * Carrega o MOTIVO da negativa, e nao apenas null. Um envio recusado por falta
 * de vinculo e um recusado por COMPDEC sem municipio pedem acoes diferentes do
 * operador -- um precisa de vinculo, o outro precisa que alguem corrija o
 * cadastro do orgao. Devolver null para os dois obrigaria a tela a mostrar
 * "sem permissao" e deixar a pessoa sem saber o que fazer.
 */
final readonly class ProcedenciaDTO
{
    private function __construct(
        public bool $permitido,
        public ?int $municipioId,
        public ?string $municipioNome,
        public ?int $orgaoId,
        public ?string $orgaoNome,
        public ?string $motivo,
    ) {
    }

    public static function permitida(
        int $municipioId,
        string $municipioNome,
        int $orgaoId,
        string $orgaoNome,
    ): self {
        return new self(true, $municipioId, $municipioNome, $orgaoId, $orgaoNome, null);
    }

    public static function negada(string $motivo): self
    {
        return new self(false, null, null, null, null, $motivo);
    }

    /**
     * Acesso ao municipio com garantia de que houve permissao.
     *
     * Existe para que um caminho de codigo que esqueca de checar $permitido
     * estoure aqui, em vez de gravar a camada com municipio_id null e ser
     * recusado pelo CHECK do banco com mensagem incompreensivel.
     */
    public function municipioId(): int
    {
        if (! $this->permitido || $this->municipioId === null) {
            throw new LogicException('Procedencia negada: nao ha municipio a atribuir. Verifique $permitido antes.');
        }

        return $this->municipioId;
    }
}
