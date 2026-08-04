<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Support;

use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use Illuminate\Database\Eloquent\Model;

/**
 * Liga a trilha de acoes num model de protocolo.
 *
 * O modulo escreve `use TrilhaDeAcoes;` e `implements Rastreavel`, e o trait cobre os
 * hooks Eloquent mais os defaults do contrato. Sobram tres metodos obrigatorios:
 * moduloNotificacao(), rotuloProtocolo() e donosNotificacao() -- o que o trait nao
 * tem como adivinhar.
 *
 * Fica em trait, e nao em observer por modulo, porque o gatilho e sempre o mesmo: o
 * que varia e a DECLARACAO do protocolo, nao o comportamento. Cada observer proprio
 * era mais uma copia da mesma regra para sair de sincronia com as outras.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \App\Modules\Notificacoes\Contracts\Rastreavel
 */
trait TrilhaDeAcoes
{
    public static function bootTrilhaDeAcoes(): void
    {
        static::updated(function (Model $model): void {
            $model->reportarAtualizacaoNaTrilha();
        });

        static::deleted(function (Model $model): void {
            $model->reportarRemocaoNaTrilha();
        });
    }

    // ─── Defaults do contrato Rastreavel ────────────────────────────────────

    /**
     * Nome curto vem de config('notificacoes.modulos.{slug}.nome_curto'), a mesma
     * fonte unica que ja define label, icone e janela do modulo. Sem a chave, cai no
     * label -- feio, mas visivel, o que faz o ajuste aparecer em vez de passar batido.
     */
    public function nomeCurtoNotificacao(): string
    {
        $modulo = (array) config('notificacoes.modulos.'.$this->moduloNotificacao(), []);

        return (string) ($modulo['nome_curto'] ?? $modulo['label'] ?? 'Registro');
    }

    public function urlNotificacao(): ?string
    {
        return null;
    }

    public function acaoTextoNotificacao(): string
    {
        return 'Abrir '.$this->nomeCurtoNotificacao();
    }

    /**
     * Null por padrao: virada de situacao e opt-in. Assim um modulo que ja tem
     * gatilho proprio de status (Demandas, PAE) adota a trilha sem gerar aviso
     * duplicado da mesma mudanca.
     */
    public function campoSituacao(): ?string
    {
        return null;
    }

    public function rotuloSituacao(): ?string
    {
        return null;
    }

    public function detalheSituacao(): ?string
    {
        return null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    public function camposIgnoradosNaTrilha(): array
    {
        return $this->camposBaseIgnoradosNaTrilha();
    }

    /**
     * Base que vale para qualquer protocolo. Fica separada para o model poder ESTENDER
     * a lista sem reescrever esta parte:
     *
     *     return array_merge($this->camposBaseIgnoradosNaTrilha(), ['historico']);
     *
     * @return list<string>
     */
    protected function camposBaseIgnoradosNaTrilha(): array
    {
        // Colunas de infraestrutura e de autoria: mudam em toda escrita e nao dizem
        // nada ao dono do protocolo.
        return ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'];
    }

    // ─── Gatilhos ───────────────────────────────────────────────────────────

    /**
     * Situacao ganha da edicao quando as duas mudam no mesmo salvamento: um card por
     * fato, e o fato mais especifico e o que muda o que o dono pode fazer.
     */
    protected function reportarAtualizacaoNaTrilha(): void
    {
        $alterados = array_values(array_diff(
            array_keys($this->getChanges()),
            $this->camposIgnoradosNaTrilha(),
        ));

        if ($alterados === []) {
            return;
        }

        $campoSituacao = $this->campoSituacao();

        if ($campoSituacao !== null && in_array($campoSituacao, $alterados, true)) {
            $this->registrarNaTrilha(AcaoTrilha::Situacao);

            return;
        }

        $this->registrarNaTrilha(AcaoTrilha::Editado);
    }

    /**
     * Soft delete e force delete contam igual: exclusao.
     *
     * O dono aperta UM botao de excluir e o protocolo sai da tela dele. Que a linha
     * continue no banco com deleted_at e detalhe de implementacao -- dizer "arquivado"
     * para quem clicou em excluir so gera duvida sobre o que aconteceu de fato.
     *
     * O evento deleted dispara nos dois casos, entao um hook basta.
     */
    protected function reportarRemocaoNaTrilha(): void
    {
        $this->registrarNaTrilha(AcaoTrilha::Excluido);
    }

    protected function registrarNaTrilha(AcaoTrilha $acao, ?string $detalhe = null): void
    {
        app(RegistroDeAcao::class)->registrar($this, $acao, $detalhe);
    }
}
