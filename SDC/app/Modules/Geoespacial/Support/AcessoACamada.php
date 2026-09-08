<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Support;

use App\Models\User;
use App\Modules\Geoespacial\Enums\StatusCamada;

/**
 * Quem pode fazer o que com UMA camada.
 *
 * Vive em Support/ e nao em Services/ por ser regra transversal e sem efeito
 * -- mesmo lugar de EscopoPerfil (Cisterna) e DestinatariosPmda (Pmda), que
 * respondem pergunta em vez de executar acao. Services/ neste modulo guarda
 * quem MUDA estado: RevisaoDeCamadas e CicloDeVidaDaCamada.
 *
 * Nao e Policy do Laravel porque a camada nao e Model Eloquent -- ela vive em
 * silver.geo_camadas, camada Silver do medalhao, e chega as telas como objeto
 * de consulta. Policy exige model.
 *
 * A permissao (RBAC) responde "esta pessoa tem a capacidade?". Este servico
 * responde "nesta camada, neste estado?". As duas perguntas sao necessarias: a
 * permissao de editar nao autoriza editar a camada de outro municipio, nem
 * editar uma camada aprovada que ja esta no mapa do estado.
 *
 * O resultado desce para a tela e alimenta o `allowed` do ActionButton, que ja
 * cuida do RBAC por conta propria. Os dois filtros somados sao o que fecha o
 * botao: sem RBAC ele nao aparece; sem regra de estado ele aparece e o servidor
 * recusa -- e botao que aparece para falhar e pior que botao ausente.
 */
final class AcessoACamada
{
    /**
     * @return array{editar: bool, arquivar: bool, reativar: bool, baixar: bool, reprocessar: bool}
     */
    public function acoes(?User $usuario, object $camada, ?int $municipioDoUsuario = null): array
    {
        if ($usuario === null) {
            return ['editar' => false, 'arquivar' => false, 'reativar' => false, 'baixar' => false, 'reprocessar' => false];
        }

        $revisa = $usuario->can('geoespacial.camadas.revisar');
        $minha = $this->ehMinha($usuario, $camada, $municipioDoUsuario);
        $status = StatusCamada::deBanco($camada->status ?? null);

        return [
            /*
             * Editar metadado.
             *
             * Para quem NAO revisa, so em camada pendente ou recusada. Camada
             * aprovada esta no mapa operacional do estado: deixar o municipio
             * trocar o nivel de "baixo" para "muito_alto" -- ou a data de
             * emissao -- depois da aprovacao faria a moderacao valer apenas
             * para o primeiro instante da camada.
             */
            'editar' => $usuario->can('geoespacial.camadas.edit')
                && $status !== StatusCamada::ARQUIVADA
                && ($revisa || ($minha && $status->editavelPeloRemetente())),

            // Arquivar e reativar sao decisao sobre o mapa do estado: exigem a
            // permissao propria, que so a CEDEC tem. Ver a maquina de estado em
            // CicloDeVidaDaCamada.
            'arquivar' => $usuario->can('geoespacial.camadas.arquivar') && $status->arquivavel(),
            'reativar' => $usuario->can('geoespacial.camadas.arquivar') && $status->reativavel(),

            // Baixar o original so existe onde ha original: camada estadual
            // publica direto e nao guarda arquivo (arquivo_caminho NULL).
            'baixar' => $usuario->can('geoespacial.camadas.export')
                && ! empty($camada->arquivo_caminho)
                && ($revisa || $minha),

            // Reprocessar refaz geometria no mapa do estado e depende do Bronze
            // ainda existir. Sem permissao propria de proposito: e manutencao,
            // nao fluxo -- quem revisa e quem responde pela geometria publicada.
            'reprocessar' => $revisa && ! empty($camada->ingestao_id),
        ];
    }

    /**
     * Pode ABRIR esta camada?
     *
     * Aprovada e publica para quem tem a permissao de leitura -- e o mapa do
     * estado. Pendente, recusada e arquivada nao: elas carregam motivo de
     * recusa e geometria que ninguem validou, e o municipio A nao tem o que
     * fazer com o rascunho do municipio B.
     *
     * E o mesmo recorte de GeoCamadaRepository::camadas(). Repetir a regra aqui
     * e proposital: sem isto a listagem esconderia a camada e /geoespacial/{id}
     * digitado na barra de endereco a abriria -- esconder na lista nao e
     * autorizacao.
     */
    public function podeVer(?User $usuario, object $camada, ?int $municipioDoUsuario = null): bool
    {
        if ($usuario === null || ! $usuario->can('geoespacial.camadas.view')) {
            return false;
        }

        if ($usuario->can('geoespacial.camadas.revisar')) {
            return true;
        }

        if (StatusCamada::deBanco($camada->status ?? null)->noMapa()) {
            return true;
        }

        return $this->ehMinha($usuario, $camada, $municipioDoUsuario);
    }

    /**
     * A camada e do usuario?
     *
     * Por autoria OU por municipio, os dois. A COMPDEC tem mais de uma pessoa:
     * o agente que entra hoje precisa poder tratar do envio que o colega do
     * mesmo municipio fez ontem. E o mesmo criterio de
     * GeoCamadaRepository::minhasCamadas(), de proposito -- se a lista mostra,
     * a acao tem de valer.
     */
    private function ehMinha(User $usuario, object $camada, ?int $municipioDoUsuario): bool
    {
        if (isset($camada->enviado_por) && (int) $camada->enviado_por === (int) $usuario->id) {
            return true;
        }

        return $municipioDoUsuario !== null
            && isset($camada->municipio_id)
            && (int) $camada->municipio_id === $municipioDoUsuario;
    }
}
