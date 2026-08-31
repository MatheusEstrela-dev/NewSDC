<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use Illuminate\Database\Eloquent\Model;

/**
 * Caminho unico da trilha de acoes: recebe "isto aconteceu com este protocolo" e
 * decide se, para quem e com que texto isso vira card no sino.
 *
 * Nenhum modulo repete as regras abaixo, que sao a diferenca entre uma trilha util e
 * um sino que o usuario aprende a ignorar:
 *
 * 1. Sem usuario autenticado, nao notifica. Fila, console e importacao de legado nao
 *    tem autor -- e a importacao do arquivo morto do RAT despejaria um card por
 *    registro importado no sino de quem rodou o comando.
 * 2. O autor da acao nunca e avisado do que ele mesmo fez.
 * 3. Uma notificacao por (protocolo, acao) por requisicao. Um unico salvamento de RAT
 *    grava dados gerais, recursos, envolvidos e vistoria em models separados; sem isso
 *    o dono receberia o mesmo "editado" uma vez por linha tocada.
 *
 * A entrega em si nao acontece aqui: sai um EntregarNotificacaoJob e a requisicao do
 * usuario termina. Preferencia, agrupamento, escrita e contador ficam no worker.
 */
class RegistroDeAcao
{
    /**
     * Chaves (protocolo, acao) ja notificadas nesta requisicao.
     *
     * ATENCAO: este estado SO pode viver o tempo de uma requisicao, e por isso o
     * binding em AppServiceProvider e scoped() e nao singleton(). Sob Octane um
     * singleton sobreviveria entre requisicoes e a lista passaria a suprimir
     * notificacoes legitimas para sempre -- sem erro nenhum no log.
     *
     * @var array<string, true>
     */
    private array $jaNotificado = [];

    /**
     * Entrada do proprio protocolo, com o model em maos.
     *
     * @param  string|null  $detalhe  o que foi acrescentado, apenas para Relacionado
     */
    public function registrar(Rastreavel&Model $registro, AcaoTrilha $acao, ?string $detalhe = null): void
    {
        $autor = $this->autor();

        if ($autor === null || $this->repetido($registro::class, $registro->getKey(), $acao)) {
            return;
        }

        $this->despachar($registro, $acao, $autor, $detalhe);
    }

    /**
     * Entrada do lado-filho: anexo, comentario ou relato reportando algo ao protocolo
     * pai, que e identificado por classe e chave em vez de model carregado.
     *
     * O pai so vai ao banco DEPOIS das checagens baratas (autor e repeticao). Sem essa
     * ordem, salvar um RAT com dez relatos custaria dez SELECTs no protocolo para
     * mandar uma notificacao so.
     *
     * @param  class-string  $classeDoProtocolo
     */
    public function registrarNoProtocolo(
        string $classeDoProtocolo,
        int|string|null $chaveDoProtocolo,
        AcaoTrilha $acao,
        ?string $detalhe = null,
    ): void {
        $autor = $this->autor();

        if ($autor === null || $chaveDoProtocolo === null) {
            return;
        }

        if ($this->repetido($classeDoProtocolo, $chaveDoProtocolo, $acao)) {
            return;
        }

        $protocolo = $classeDoProtocolo::find($chaveDoProtocolo);

        // Protocolo inexistente ou que nao declarou o contrato: nada a notificar.
        if (!$protocolo instanceof Rastreavel || !$protocolo instanceof Model) {
            return;
        }

        $this->despachar($protocolo, $acao, $autor, $detalhe);
    }

    private function despachar(
        Rastreavel&Model $registro,
        AcaoTrilha $acao,
        string $autor,
        ?string $detalhe,
    ): void {
        $destinatarios = $this->destinatarios($registro);

        if ($destinatarios === []) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            $this->spec($registro, $acao, $autor, $detalhe),
            $destinatarios,
        );
    }

    /**
     * Nome de quem executou a acao, ou null quando nao ha usuario autenticado.
     *
     * Os guards extras vem de config('notificacoes.guards_autores') porque nem toda
     * acao sobre um protocolo parte de usuario interno: no Portal de Treinamentos
     * quem se inscreve e um cidadao, autenticado em guard proprio. Olhando so o
     * guard padrao, auth()->user() voltava null e registrar() saia sem fazer nada --
     * o dono do treinamento nunca sabia das inscricoes, que e justamente o aviso
     * mais util do modulo.
     */
    private function autor(): ?string
    {
        $usuario = auth()->user();

        foreach (config('notificacoes.guards_autores', []) as $guard) {
            if ($usuario !== null) {
                break;
            }

            $usuario = auth($guard)->user();
        }

        if ($usuario === null) {
            return null;
        }

        $nome = trim((string) ($usuario->name ?? ''));

        return $nome === '' ? 'outro usuario' : $nome;
    }

    /**
     * Donos do protocolo, sem duplicatas e sem quem executou a acao.
     *
     * @return list<int>
     */
    private function destinatarios(Rastreavel $registro): array
    {
        $autorId = (int) auth()->id();

        return array_values(array_unique(array_filter(
            array_map('intval', $registro->donosNotificacao()),
            fn (int $id): bool => $id > 0 && $id !== $autorId,
        )));
    }

    private function repetido(string $classe, int|string|null $chave, AcaoTrilha $acao): bool
    {
        $referencia = $classe.':'.(string) $chave.':'.$acao->value;

        if (isset($this->jaNotificado[$referencia])) {
            return true;
        }

        $this->jaNotificado[$referencia] = true;

        return false;
    }

    // ─── Texto do card ──────────────────────────────────────────────────────
    //
    // O formato e sempre o mesmo, para o usuario ler o card sem reaprender: o TITULO
    // diz o que aconteceu e a MENSAGEM diz em qual protocolo e por quem. Saber quem
    // mexeu e o dado que faz o dono decidir se aquilo era esperado -- sem isso ele
    // precisa abrir o protocolo so para descobrir.
    //
    // Montar a frase aqui, e nao em cada modulo, e o que impede uma chave interna de
    // voltar a vazar para o usuario: o rotulo vem do contrato, que proibe isso.

    private function spec(
        Rastreavel&Model $registro,
        AcaoTrilha $acao,
        string $autor,
        ?string $detalhe,
    ): NotificacaoSpec {
        return new NotificacaoSpec(
            modulo: $registro->moduloNotificacao(),
            titulo: $this->titulo($registro, $acao, $detalhe),
            mensagem: $this->mensagem($registro, $acao, $autor, $detalhe),
            tipo: $this->tipo($registro, $acao),
            // A chave carrega a acao, e nao apenas o protocolo: cinco edicoes seguidas
            // viram um card com contador, mas a virada de situacao abre card proprio em
            // vez de ser absorvida pelo card de edicao. O prefixo antes do primeiro
            // ":" precisa ser o slug do modulo -- e por ele que GeneralNotification
            // deriva o modulo quando a spec nao vem do dispatcher.
            groupKey: sprintf(
                '%s:%s:%s',
                $registro->moduloNotificacao(),
                (string) $registro->getKey(),
                $acao->value,
            ),
            acaoUrl: $registro->urlNotificacao(),
            acaoTexto: $registro->acaoTextoNotificacao(),
        );
    }

    private function titulo(Rastreavel $registro, AcaoTrilha $acao, ?string $detalhe): string
    {
        $nome = $registro->nomeCurtoNotificacao();

        return match ($acao) {
            AcaoTrilha::Editado => "{$nome} atualizado",
            AcaoTrilha::Excluido => "{$nome} excluido",
            // "Anexo no RAT" diz mais que "RAT atualizado" quando o que mudou foi
            // conteudo de terceiro.
            AcaoTrilha::Relacionado => $this->vazio($detalhe)
                ? "Novidade no {$nome}"
                : ucfirst($this->semArtigo((string) $detalhe))." no {$nome}",
            // "RAT finalizado", "Demanda em andamento": o rotulo da situacao ja e o que
            // o modulo mostra na tela, entao o card fala a mesma lingua da tela.
            AcaoTrilha::Situacao => trim($nome.' '.mb_strtolower((string) $registro->rotuloSituacao())),
        };
    }

    private function mensagem(
        Rastreavel $registro,
        AcaoTrilha $acao,
        string $autor,
        ?string $detalhe,
    ): string {
        $protocolo = $registro->rotuloProtocolo();

        if ($acao === AcaoTrilha::Relacionado) {
            $oQue = $this->vazio($detalhe) ? 'uma novidade' : trim((string) $detalhe);

            return "{$protocolo} recebeu {$oQue} de {$autor}.";
        }

        if ($acao === AcaoTrilha::Situacao) {
            $frase = sprintf(
                '%s foi %s por %s',
                $protocolo,
                mb_strtolower((string) $registro->rotuloSituacao()),
                $autor,
            );
            $complemento = $registro->detalheSituacao();

            if ($this->vazio($complemento)) {
                return $frase.'.';
            }

            // Frase e complemento sao DUAS oracoes: sem o ponto entre elas saia
            // "por Joao Motivo: ...", e o rtrim evita o ponto duplo quando o
            // modulo ja entrega o complemento pontuado (motivo digitado pelo
            // usuario quase sempre termina em ponto).
            return $frase.'. '.rtrim(trim((string) $complemento), '.').'.';
        }

        return "{$protocolo} foi {$acao->verbo()} por {$autor}.";
    }

    /**
     * O modulo tem a ultima palavra sobre a gravidade da virada de situacao: so ele
     * sabe se "Finalizado" e conquista ou se "Indeferido" e problema. Sem opiniao
     * declarada, vale o default da acao.
     */
    private function tipo(Rastreavel $registro, AcaoTrilha $acao): string
    {
        if ($acao !== AcaoTrilha::Situacao) {
            return $acao->tipo();
        }

        $tipo = $registro->tipoSituacaoNotificacao();

        return in_array($tipo, NotificacaoSpec::TIPOS, true) ? (string) $tipo : $acao->tipo();
    }

    /**
     * "um novo anexo" -> "novo anexo", para o titulo nao sair como "Um novo anexo no
     * RAT". A mensagem continua usando a forma com artigo.
     */
    private function semArtigo(string $texto): string
    {
        $texto = trim($texto);

        foreach (['um ', 'uma ', 'novos ', 'novas '] as $prefixo) {
            if (str_starts_with(mb_strtolower($texto), $prefixo)) {
                return mb_substr($texto, mb_strlen($prefixo));
            }
        }

        return $texto;
    }

    private function vazio(?string $texto): bool
    {
        return $texto === null || trim($texto) === '';
    }
}
