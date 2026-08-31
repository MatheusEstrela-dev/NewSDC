<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Support;

use App\Models\User;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Quem precisa saber do que aconteceu com um PMDA.
 *
 * O plano e um protocolo de DUAS pontas -- o municipio prepara e envia, a CEDEC
 * decide -- e a trilha avisava so o created_by, nos dois sentidos. Na pratica isso
 * significava que ninguem da CEDEC ficava sabendo de um envio novo (a fila so
 * enchia para quem abrisse a tela), e que uma devolutiva morria se quem criou o
 * plano tivesse saido de licenca.
 *
 * A direcao e lida da SITUACAO do plano, e nao da acao: o contrato Rastreavel
 * chama donosNotificacao() sem dizer o que aconteceu, e a situacao ja e o dado
 * que diz de que lado a bola esta.
 */
final class DestinatariosPmda
{
    /**
     * @return list<int>
     */
    public static function paraPlano(PmdaPlano $plano): array
    {
        $criador = $plano->created_by === null ? [] : [(int) $plano->created_by];

        $contraparte = match (true) {
            // Bola com a CEDEC: o municipio acabou de enviar para analise.
            $plano->status === PmdaStatus::EM_ANALISE => self::analistasDaCedec(),

            // Bola de volta com o municipio: a CEDEC decidiu (aprovou, arquivou
            // ou devolveu). Devolucao volta o plano para RASCUNHO, entao o que a
            // distingue de uma edicao comum e o pedido_altera.
            self::decisaoDaCedec($plano) => self::equipeDoMunicipio($plano),

            // Edicao comum do municipio no proprio rascunho: avisar o time inteiro
            // a cada salvamento seria o tipo de ruido que ensina a ignorar o sino.
            default => [],
        };

        return array_values(array_unique(array_merge($criador, $contraparte)));
    }

    private static function decisaoDaCedec(PmdaPlano $plano): bool
    {
        return in_array($plano->status, [PmdaStatus::APROVADO, PmdaStatus::ARQUIVADO], true)
            || ($plano->pedido_altera && $plano->status === PmdaStatus::RASCUNHO);
    }

    /**
     * Equipe COMPDEC do municipio do plano: quem esta lotado no orgao pela coluna
     * principal ou pelo pivot. Os dois caminhos existem porque orgao_principal_id
     * nem sempre vem preenchido -- mesma razao de OrgaoDeLotacao ter fallback.
     *
     * @return list<int>
     */
    private static function equipeDoMunicipio(PmdaPlano $plano): array
    {
        if ($plano->municipio_id === null) {
            return [];
        }

        $orgaos = Orgao::query()
            ->where('tipo', TipoOrgao::COMPDEC->value)
            ->where('municipio_id', $plano->municipio_id)
            ->pluck('id');

        if ($orgaos->isEmpty()) {
            return [];
        }

        return User::query()
            ->where(fn ($q) => $q
                ->whereIn('orgao_principal_id', $orgaos)
                ->orWhereHas('orgaos', fn ($o) => $o->whereIn('compdec_orgaos.id', $orgaos)))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Quem analisa PMDA na CEDEC -- definido pela PERMISSAO, e nao por estar no
     * orgao: dentro da CEDEC nem todo mundo trabalha a fila do PMDA, e mandar o
     * card para o orgao inteiro faria o aviso perder o valor para quem decide.
     *
     * @return list<int>
     */
    private static function analistasDaCedec(): array
    {
        try {
            return User::query()
                ->permission('pmda.analise.view')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        } catch (PermissionDoesNotExist) {
            // Base sem o permissionamento semeado: sem analista declarado nao ha
            // quem avisar, e derrubar o salvamento do plano por causa do aviso
            // seria trocar um problema pequeno por um grande.
            return [];
        }
    }
}
