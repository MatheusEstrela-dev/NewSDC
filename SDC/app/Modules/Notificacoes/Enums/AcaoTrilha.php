<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Enums;

/**
 * As acoes de sistema que o dono de um protocolo precisa acompanhar.
 *
 * Cada caso e um FATO distinto sobre o protocolo, e nao um nivel de detalhe do
 * mesmo fato: por isso cada um abre seu proprio card no sino em vez de todos
 * caiirem no generico "registro alterado". Uma virada de situacao muda o que o
 * dono PODE fazer; uma edicao muda o que o protocolo DIZ; um vinculo acrescenta
 * conteudo de terceiro. Confundir os tres tira do usuario justamente a informacao
 * que o faria decidir se precisa agir.
 *
 * Nao existe caso "criado" de proposito: quem cria e o dono, e ninguem precisa ser
 * avisado do que acabou de fazer.
 */
enum AcaoTrilha: string
{
    case Editado = 'editado';

    case Situacao = 'situacao';

    case Relacionado = 'relacionado';

    /**
     * Vale tanto para soft delete quanto para force delete.
     *
     * Nao existe caso "arquivado" separado por dois motivos. O usuario aperta UM botao
     * de excluir e o registro sai da tela dele -- que a linha continue no banco com
     * deleted_at e detalhe de implementacao, nao noticia. E "arquivado" ja significa
     * outra coisa aqui: a tela Arquivados do RAT e o arquivo morto do legado
     * (LegadoRatController), nao registro apagado.
     */
    case Excluido = 'excluido';

    /**
     * Tipo do card, entre os aceitos por NotificacaoSpec::TIPOS. E o que define a cor,
     * o icone e a cor do botao no sino (ver NotificationItem.vue).
     *
     * O codigo de cor e por GRAVIDADE do que aconteceu com o protocolo, para o dono
     * priorizar pela cor antes de ler:
     *
     *   vermelho (error)  exclusao     -- o protocolo saiu da tela do dono
     *   ambar   (warning) edicao       -- o conteudo mudou e pode nao ser o esperado
     *   azul    (info)    relacionado  -- alguem acrescentou algo, nada se perdeu
     *
     * urgent nao entra aqui: alem do vermelho com glow, ele desvia o job para a fila de
     * alta prioridade (NotificacaoSpec::ehUrgente), e nenhuma acao de trilha justifica
     * furar a fila.
     *
     * Situacao nao decide aqui: quem sabe se a virada e boa ou ruim e o modulo, via
     * Rastreavel::tipoSituacaoNotificacao().
     */
    public function tipo(): string
    {
        return match ($this) {
            self::Relacionado => 'info',
            self::Situacao => 'info',
            self::Editado => 'warning',
            self::Excluido => 'error',
        };
    }

    /**
     * Verbo em terceira pessoa usado na frase "{protocolo} foi {verbo} por {autor}".
     *
     * Situacao nao tem verbo fixo: o texto usa o proprio rotulo da situacao nova
     * ("foi finalizado", "foi arquivado pelo municipio"), que so o modulo conhece.
     */
    public function verbo(): ?string
    {
        return match ($this) {
            self::Editado => 'editado',
            self::Excluido => 'excluido',
            self::Relacionado, self::Situacao => null,
        };
    }
}
