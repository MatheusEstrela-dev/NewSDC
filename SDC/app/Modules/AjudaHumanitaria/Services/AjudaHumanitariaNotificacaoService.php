<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;

/**
 * Avisos do processo de Ajuda Humanitaria.
 *
 * O modulo tinha o fluxo inteiro mudo: o pedido tramitava, o parecer saia e a
 * prestacao era homologada sem que ninguem fosse avisado. Quem abriu o pedido
 * so descobria a mudanca abrindo a tela.
 *
 * O slug 'ajuda-humanitaria' ja estava declarado em config/notificacoes.php,
 * com rotulo e icone: faltava apenas emitir.
 *
 * Cada metodo monta a spec e delega ao job. Nada aqui sabe de canal, inbox ou
 * preferencia de usuario, que sao responsabilidade do dispatcher.
 */
class AjudaHumanitariaNotificacaoService
{
    private const MODULO = 'ajuda-humanitaria';

    /**
     * Avisa a mudanca de etapa do pedido.
     *
     * Vai para quem abriu o pedido, mais o analista e o diretor quando ja estao
     * definidos. O autor da acao fica de fora: ninguem precisa ser avisado do
     * que acabou de fazer.
     */
    public function pedidoTramitado(
        PedidoAh $pedido,
        StatusPedidoAh $origem,
        StatusPedidoAh $destino,
        ?int $autorId,
    ): void {
        $destinatarios = $this->envolvidos($pedido, $autorId);

        if ($destinatarios === []) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: self::MODULO,
                titulo: 'Pedido '.$pedido->identificador.' mudou de etapa',
                mensagem: sprintf(
                    'De "%s" para "%s".%s',
                    $origem->label(),
                    $destino->label(),
                    match (true) {
                        $destino === StatusPedidoAh::Atendido   => ' A prestação de contas foi aberta.',
                        // Finalizado so se alcanca por homologacao (RN-19),
                        // entao nao existe aviso separado de homologacao: seria
                        // uma segunda notificacao para o mesmo fato.
                        $destino === StatusPedidoAh::Finalizado => ' A prestação de contas foi homologada.',
                        default                                 => '',
                    },
                ),
                tipo: $this->tipoPorStatus($destino),
                // Agrupa por pedido: uma sequencia de tramites no mesmo
                // processo vira um card so, em vez de encher a caixa.
                groupKey: self::MODULO.':pedido:'.$pedido->getKey(),
                acaoUrl: '/ajuda-humanitaria/pedidos/'.$pedido->getKey(),
                acaoTexto: 'Ver pedido',
            ),
            $destinatarios,
        );
    }

    /**
     * Avisa que saiu parecer tecnico.
     *
     * Parecer desfavoravel entra como aviso, nao como informacao: costuma
     * exigir acao do municipio.
     */
    public function parecerEmitido(PedidoAh $pedido, SituacaoParecer $situacao, ?int $autorId): void
    {
        $destinatarios = $this->envolvidos($pedido, $autorId);

        if ($destinatarios === []) {
            return;
        }

        $favoravel = $situacao === SituacaoParecer::Favoravel;

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: self::MODULO,
                titulo: 'Parecer '.($favoravel ? 'favorável' : 'desfavorável').' no pedido '.$pedido->identificador,
                mensagem: $favoravel
                    ? 'O pedido recebeu parecer favorável e segue no fluxo.'
                    : 'O pedido recebeu parecer desfavorável. Verifique a justificativa.',
                tipo: $favoravel ? 'success' : 'warning',
                groupKey: self::MODULO.':pedido:'.$pedido->getKey(),
                acaoUrl: '/ajuda-humanitaria/pedidos/'.$pedido->getKey(),
                acaoTexto: 'Ver parecer',
            ),
            $destinatarios,
        );
    }

    /**
     * Avisa a abertura da prestacao de contas, com o prazo.
     *
     * E o aviso mais acionavel do modulo: passado o prazo, o municipio fica
     * inadimplente.
     */
    public function prestacaoAberta(PedidoAh $pedido, ?string $dataLimite): void
    {
        $destinatarios = $this->envolvidos($pedido, null);

        if ($destinatarios === []) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: self::MODULO,
                titulo: 'Prestação de contas aberta: pedido '.$pedido->identificador,
                mensagem: $dataLimite !== null
                    ? 'Lance as entregas até '.$dataLimite.'.'
                    : 'Lance as entregas do material recebido.',
                tipo: 'warning',
                groupKey: self::MODULO.':prestacao:'.$pedido->getKey(),
                acaoUrl: '/ajuda-humanitaria/pedidos/'.$pedido->getKey(),
                acaoTexto: 'Lançar entregas',
            ),
            $destinatarios,
        );
    }

    /**
     * Quem acompanha o processo: autor do pedido, analista e diretor.
     *
     * Remove o autor da acao e duplicatas. Devolve lista de ids, que e o que o
     * job espera: model serializado no Redis fica desatualizado.
     *
     * @return list<int>
     */
    private function envolvidos(PedidoAh $pedido, ?int $autorId): array
    {
        $ids = array_filter([
            $pedido->created_by,
            $pedido->analista_id,
            $pedido->diretor_id,
        ]);

        $ids = array_diff(array_unique($ids), array_filter([$autorId]));

        return array_values(array_map('intval', $ids));
    }

    /**
     * Etapa de decisao merece destaque; o resto e informacao de andamento.
     */
    private function tipoPorStatus(StatusPedidoAh $destino): string
    {
        return match ($destino) {
            StatusPedidoAh::Cancelado,
            StatusPedidoAh::Reprovado  => 'error',
            StatusPedidoAh::Aprovado,
            StatusPedidoAh::Atendido,
            StatusPedidoAh::Finalizado => 'success',
            default                    => 'info',
        };
    }
}
