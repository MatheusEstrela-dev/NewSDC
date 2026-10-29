<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain;

use App\Models\User;
use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Models\Cronograma;

/**
 * Guardas da tramitacao do cronograma (mesmo papel de PedidoAhWorkflow).
 *
 * Devolve o MOTIVO do bloqueio, ou null quando a acao pode seguir -- o mesmo
 * contrato de CronoViagem::motivoParaNaoAprovar, para a tela mostrar a frase
 * em vez de so esconder o botao.
 *
 * As guardas da ativacao (vistoria, caminhao, um cronograma por municipio)
 * nao estao aqui: continuam em CronogramaService::podeAtivar, que a acao
 * `ativar` chama. Duplicar a regra seria abrir caminho para as duas divergirem.
 */
final class CronogramaWorkflow
{
    public function motivoParaNaoTramitar(Cronograma $cronograma, AcaoTramite $acao, ?User $user, bool $checarPermissao = true): ?string
    {
        if ($cronograma->arquivado_em !== null) {
            return 'Cronograma arquivado não tramita. Desarquive antes.';
        }

        $fase = $cronograma->fase_tramite;

        if ($fase->destino($acao) === null) {
            return "A ação \"{$acao->label()}\" não se aplica à fase \"{$fase->label()}\".";
        }

        if ($checarPermissao) {
            $permissao = $fase->permissaoPara($acao);
            if ($user === null || $permissao === null || ! $user->can($permissao)) {
                return "Somente o responsável pela fase ({$fase->responsavel()}) pode \"{$acao->label()}\" neste cronograma.";
            }
        }

        $segregacao = $this->motivoDeSegregacao($cronograma, $acao, $user);
        if ($segregacao !== null) {
            return $segregacao;
        }

        if ($acao === AcaoTramite::Enviar) {
            $cronograma->loadCount('caminhoes');
            if ((int) $cronograma->caminhoes_count === 0) {
                return 'Aloque ao menos 1 caminhão antes de enviar para análise.';
            }
        }

        return null;
    }

    /**
     * Segregacao de funcoes: o mesmo profissional nao confere o proprio
     * trabalho. Quem enviou para analise nao aprova, e quem aprovou nao ativa.
     *
     * Vale o ULTIMO autor do passo anterior: devolvido e reenviado por outra
     * pessoa, o primeiro remetente pode aprovar. Desligavel por
     * config('tdap.cronogramas.segregacao_funcoes') para equipe de uma pessoa.
     */
    private function motivoDeSegregacao(Cronograma $cronograma, AcaoTramite $acao, ?User $user): ?string
    {
        if ($user === null || ! config('tdap.cronogramas.segregacao_funcoes', true)) {
            return null;
        }

        $passoAnterior = match ($acao) {
            AcaoTramite::Aprovar => AcaoTramite::Enviar,
            AcaoTramite::Ativar  => AcaoTramite::Aprovar,
            default              => null,
        };
        if ($passoAnterior === null) {
            return null;
        }

        $autor = $cronograma->tramites()
            ->where('acao', $passoAnterior->value)
            ->reorder('id', 'desc')
            ->value('user_id');

        if ($autor !== null && (int) $autor === (int) $user->id) {
            return match ($acao) {
                AcaoTramite::Aprovar => 'Quem enviou o cronograma para análise não pode aprová-lo. Outro analista precisa aprovar.',
                default              => 'Quem aprovou o cronograma não pode ativá-lo. Outro gestor precisa ativar.',
            };
        }

        return null;
    }
}
