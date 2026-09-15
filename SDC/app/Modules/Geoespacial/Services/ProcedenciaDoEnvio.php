<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Services;

use App\Models\User;
use App\Modules\Geoespacial\DTOs\ProcedenciaDTO;
use Illuminate\Support\Facades\DB;

/**
 * Descobre em nome de qual municipio um usuario pode enviar camada.
 *
 * Esta e a peca de seguranca do envio municipal. O municipio NAO viaja no
 * formulario: se viajasse, o municipio A poderia enviar como B trocando um
 * campo no HTML. Aqui ele e derivado do usuario autenticado, e o resultado
 * carrega o MOTIVO quando nao da -- para a tela dizer o que falta em vez de um
 * "sem permissao" seco.
 *
 * A cadeia e users.orgao_principal_id -> compdec_orgaos.municipio_id, com
 * fallback para o pivot compdec_orgao_user quando o campo do usuario esta
 * vazio. O fallback nao e teoria: medido em 2026-09-04, existe 1 usuario
 * (id 1227) com orgao_principal_id nulo e vinculo is_principal no pivot. Sem
 * ele, esse usuario nao conseguiria enviar nada, sem explicacao aparente.
 *
 * Numeros do mesmo levantamento, que explicam cada caminho abaixo:
 *   1107 usuarios, 892 com orgao -> 215 sem nenhum (perfil estadual)
 *   893 vinculos no pivot, 1 usuario com mais de um orgao
 *   35 dos 893 COMPDECs sem municipio_id
 */
final class ProcedenciaDoEnvio
{
    public function para(User $usuario): ProcedenciaDTO
    {
        $orgao = $this->orgaoPrincipal($usuario);

        if ($orgao === null) {
            // Perfil estadual cai aqui: 215 dos 1107 usuarios nao tem COMPDECent
            // nenhum. Nao e erro, e so nao ser um envio municipal.
            return ProcedenciaDTO::negada(
                'Seu usuario nao esta vinculado a nenhuma COMPDEC. '
                . 'O envio municipal exige vinculo com o orgao do municipio.'
            );
        }

        if ($orgao->municipio_id === null) {
            // 35 dos 893 COMPDECs estao sem municipio no cadastro. O envio
            // nao pode prosseguir porque a camada ficaria orfa, e o banco
            // recusaria pelo CHECK ck_silver_geo_camadas_municipal.
            return ProcedenciaDTO::negada(
                "A COMPDEC \"{$orgao->nome}\" nao tem municipio no cadastro. "
                . 'Peca a correcao do orgao antes de enviar a camada.'
            );
        }

        return ProcedenciaDTO::permitida(
            municipioId: (int) $orgao->municipio_id,
            municipioNome: (string) $orgao->municipio_nome,
            orgaoId: (int) $orgao->id,
            orgaoNome: (string) $orgao->nome,
        );
    }

    /**
     * Orgao principal, com o municipio resolvido na mesma consulta.
     *
     * Uma query so, e nao relacao do Eloquent, porque a resposta precisa de
     * tres tabelas e do fallback para o pivot -- expressar isso com relacoes
     * daria tres idas ao banco e um if em PHP para o mesmo resultado.
     *
     * COALESCE resolve a precedencia: users.orgao_principal_id vence, e o
     * is_principal do pivot entra so quando o campo do usuario esta vazio.
     * Escolhi essa ordem porque o resto do sistema le orgao_principal_id
     * (UserManagementController, PerfilCisterna, DestinatariosPmda), e divergir
     * aqui faria o envio atribuir municipio diferente do que a tela do usuario
     * mostra em outro lugar.
     */
    private function orgaoPrincipal(User $usuario): ?object
    {
        return DB::table('compdec_orgaos as o')
            ->leftJoin('municipios as m', 'm.id', '=', 'o.municipio_id')
            ->selectRaw('o.id, o.nome, o.municipio_id, m.nome as municipio_nome')
            ->whereNull('o.deleted_at')
            ->where('o.id', function ($sub) use ($usuario): void {
                $sub->selectRaw(
                    'COALESCE(
                        (SELECT u.orgao_principal_id FROM users u WHERE u.id = ?),
                        (SELECT p.orgao_id FROM compdec_orgao_user p
                          WHERE p.user_id = ? AND p.is_principal = true
                          ORDER BY p.id LIMIT 1)
                     )',
                    [$usuario->id, $usuario->id]
                );
            })
            ->first();
    }
}
