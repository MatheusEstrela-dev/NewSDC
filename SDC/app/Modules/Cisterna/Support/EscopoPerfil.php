<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Support;

use App\Modules\Cisterna\Enums\SituacaoObra;
use Illuminate\Database\Eloquent\Builder;

/**
 * Traducao do perfil do usuario em recorte de consulta.
 *
 * Existe porque a regra e a mesma para beneficiario, vistoria, comunidade e
 * notificacao, e o legado a tinha replicada em quatro metodos do controller
 * -- um deles com o codmundv 3104452 literal no meio. Aqui e um lugar so.
 *
 * As tres formas correspondem a distancia entre a tabela consultada e o
 * municipio: o beneficiario tem a coluna, a vistoria chega por relacao, e a
 * comunidade tem a coluna com outro caminho.
 */
final class EscopoPerfil
{
    /**
     * Recorte sobre a propria tabela de beneficiarios.
     *
     * COMPDEC ve so o proprio municipio. Fornecedor nao tem territorio, mas ve
     * somente as obras que sairam para instalacao -- e o mesmo recorte que o
     * legado fazia em CisternaController.php:75.
     *
     * @param  Builder<\App\Modules\Cisterna\Models\CisternaBeneficiario>  $query
     */
    public static function aplicarEmBeneficiario(Builder $query, PerfilCisterna $perfil): void
    {
        $municipioId = $perfil->municipioId();

        if ($municipioId !== null) {
            $query->doMunicipio($municipioId);
        }

        if ($perfil->eFornecedor()) {
            $query->comSituacaoObra(SituacaoObra::visiveisAoFornecedor());
        }
    }

    /**
     * Recorte de uma tabela que alcanca o municipio por relacao ao
     * beneficiario -- vistoria e notificacao de vistoria.
     *
     * A saida antecipada nao e otimizacao prematura: sem ela, CEDEC pagaria um
     * EXISTS correlacionado em toda consulta para nao filtrar nada. Vistoria
     * tem `beneficiario_id NOT NULL` com FK CASCADE, entao nao existe linha
     * orfa que o EXISTS estivesse eliminando de tabela.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public static function aplicarViaBeneficiario(
        Builder $query,
        PerfilCisterna $perfil,
        string $relacao = 'beneficiario',
    ): void {
        if (! self::temRecorte($perfil)) {
            return;
        }

        $query->whereHas($relacao, function (Builder $beneficiario) use ($perfil): void {
            self::aplicarEmBeneficiario($beneficiario, $perfil);
        });
    }

    /**
     * Recorte por coluna de municipio na propria tabela, sem passar por
     * beneficiario -- comunidade.
     *
     * Fornecedor NAO e restringido aqui de proposito: `situacao_obra` e do
     * beneficiario, nao da comunidade, e esconder a comunidade porque nenhuma
     * obra dela saiu para instalacao quebraria o select em cascata do
     * formulario sem proteger nada.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public static function aplicarEmMunicipio(
        Builder $query,
        PerfilCisterna $perfil,
        string $coluna = 'municipio_id',
    ): void {
        $municipioId = $perfil->municipioId();

        if ($municipioId !== null) {
            $query->where($coluna, $municipioId);
        }
    }

    /**
     * Se o perfil restringe alguma coisa. CEDEC e usuario sem orgao nao
     * restringem nada.
     */
    public static function temRecorte(PerfilCisterna $perfil): bool
    {
        return $perfil->municipioId() !== null || $perfil->eFornecedor();
    }
}
