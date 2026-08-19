<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Support;

use App\Models\User;
use App\Modules\Compdec\Enums\TipoOrgao;

/**
 * Perfil do usuario no modulo Cisterna.
 *
 * O legado decidia visibilidade com `user->tipo` (cedec|compdec|externo) e
 * `user->municipio_id`, repetidos em quatro metodos do controller. Nenhuma
 * das duas colunas existe no NewSDC:
 *
 *  - o perfil institucional vem de `compdec_orgaos.tipo` (enum TipoOrgao)
 *  - o territorio vem de `compdec_orgaos.municipio_id`
 *  - o fornecedor externo, que nao tem orgao, e uma role funcional
 *
 * Value object imutavel: resolve tudo uma vez e e passado aos services.
 */
final readonly class PerfilCisterna
{
    public const ROLE_FORNECEDOR = 'cisterna_fornecedor';

    private function __construct(
        private ?TipoOrgao $tipoOrgao,
        private ?int $municipioId,
        private bool $fornecedor,
    ) {}

    public static function deUsuario(User $user): self
    {
        $orgao = $user->orgaoPrincipal;

        $tipo = null;
        if ($orgao !== null && $orgao->tipo !== null) {
            $tipo = $orgao->tipo instanceof TipoOrgao
                ? $orgao->tipo
                : TipoOrgao::tryFrom((string) $orgao->tipo);
        }

        return new self(
            tipoOrgao: $tipo,
            municipioId: $orgao?->municipio_id === null ? null : (int) $orgao->municipio_id,
            fornecedor: $user->hasRole(self::ROLE_FORNECEDOR),
        );
    }

    public function tipoOrgao(): ?TipoOrgao
    {
        return $this->tipoOrgao;
    }

    /**
     * Territorio do usuario. Null para CEDEC e para fornecedor: nenhum dos
     * dois e restrito a um municipio.
     */
    public function municipioId(): ?int
    {
        return $this->eCompdec() ? $this->municipioId : null;
    }

    public function eCedec(): bool
    {
        return $this->tipoOrgao === TipoOrgao::CEDEC;
    }

    public function eCompdec(): bool
    {
        return $this->tipoOrgao === TipoOrgao::COMPDEC;
    }

    public function eFornecedor(): bool
    {
        return $this->fornecedor;
    }
}
