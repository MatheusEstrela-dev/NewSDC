<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Support;

use App\Models\User;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Support\Perfil\OrgaoDeLotacao;

/**
 * Perfil do usuario no modulo PMDA.
 *
 * O legado decidia tudo com `user->tipo` (cedec|compdec) e `user->municipio_id`:
 * PmdaController::index() filtrava a listagem por municipio e create() gravava o
 * PMDA no municipio do usuario, sem nenhum seletor. Nenhuma das duas colunas
 * existe no NewSDC -- o perfil institucional vem de `compdec_orgaos.tipo` e o
 * territorio de `compdec_orgaos.municipio_id`.
 *
 * Value object imutavel: resolve uma vez e e passado adiante.
 */
final readonly class PerfilPmda
{
    private function __construct(
        private ?TipoOrgao $tipoOrgao,
        private ?int $municipioId,
    ) {}

    public static function deUsuario(User $user): self
    {
        $orgao = OrgaoDeLotacao::resolver($user);

        $tipo = null;
        if ($orgao !== null && $orgao->tipo !== null) {
            $tipo = $orgao->tipo instanceof TipoOrgao
                ? $orgao->tipo
                : TipoOrgao::tryFrom((string) $orgao->tipo);
        }

        return new self(
            tipoOrgao: $tipo,
            municipioId: $orgao?->municipio_id === null ? null : (int) $orgao->municipio_id,
        );
    }

    /**
     * Territorio do usuario. Null para CEDEC, REDEC e usuario sem orgao: nenhum
     * dos tres e restrito a um municipio.
     */
    public function municipioId(): ?int
    {
        return $this->eCompdec() ? $this->municipioId : null;
    }

    /**
     * Filtros de listagem com o recorte do perfil imposto por cima.
     *
     * O municipio SOBRESCREVE `municipio_id` em vez de so preencher quando
     * vazio: e isso que faz `?municipio_id=<outro>` na URL nao vazar a listagem
     * de outro municipio. Perfil estadual recebe os filtros intactos.
     *
     * Mora aqui, e nao nos controllers, porque a listagem de planos, o export e
     * a fila de analises aplicam a mesma regra.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function aplicarEscopo(array $filtros): array
    {
        if ($this->municipioId() !== null) {
            $filtros['municipio_id'] = $this->municipioId();
        }

        return $filtros;
    }

    /**
     * Quem cria PMDA e o municipio. No legado nao havia seletor: create()
     * gravava direto em Auth::user()->municipio_id, e a issue #56 pede que o
     * CEDEC nem veja a acao. Sem municipio vinculado nao ha onde gravar.
     */
    public function podeCriarPmda(): bool
    {
        return $this->municipioId() !== null;
    }

    public function eCedec(): bool
    {
        return $this->tipoOrgao === TipoOrgao::CEDEC;
    }

    public function eCompdec(): bool
    {
        return $this->tipoOrgao === TipoOrgao::COMPDEC;
    }
}
