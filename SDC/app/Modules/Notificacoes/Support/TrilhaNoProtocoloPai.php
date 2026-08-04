<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Support;

use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use Illuminate\Database\Eloquent\Model;

/**
 * Liga a trilha num registro FILHO, que reporta a acao ao protocolo pai.
 *
 * Existe porque em varios modulos o conteudo do protocolo nao mora na tabela do
 * protocolo. No RAT, editar dados gerais, recursos, envolvidos ou vistoria escreve em
 * models de relato -- rat_ocorrencias nem e tocado (RatWriteService::saveDraft ainda
 * faz mass-update no pai, que nao dispara evento nenhum). Um hook so no protocolo
 * jamais veria essas edicoes, e era exatamente por isso que a edicao de um RAT nao
 * gerava aviso para o autor.
 *
 * O filho e o unico lado que sabe apontar o pai, e aponta por CLASSE e CHAVE: assim o
 * protocolo so vai ao banco depois de o servico confirmar que ha o que notificar.
 *
 * O model que usa este trait declara tres coisas:
 *   - protocoloDaTrilhaClasse(): class-string do model do protocolo
 *   - protocoloDaTrilhaChave(): chave do pai, ou null quando orfao
 *   - acaoNaTrilhaDoProtocolo(): Editado (o filho E o conteudo do protocolo) ou
 *     Relacionado (o filho e algo pendurado nele, como anexo ou comentario)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait TrilhaNoProtocoloPai
{
    /**
     * Classe do model do protocolo pai.
     *
     * Abstrato de proposito: um model que usa o trait e esquece de declarar o pai nao
     * compila, em vez de falhar silenciosamente em producao sem notificar ninguem.
     *
     * @return class-string
     */
    abstract public function protocoloDaTrilhaClasse(): string;

    /**
     * Chave do protocolo pai, ou null quando o filho esta orfao.
     */
    abstract public function protocoloDaTrilhaChave(): int|string|null;

    /**
     * Editado quando o filho E o conteudo do protocolo (relato, item do formulario);
     * Relacionado quando ele e algo pendurado nele (anexo, comentario).
     */
    abstract public function acaoNaTrilhaDoProtocolo(): AcaoTrilha;

    public static function bootTrilhaNoProtocoloPai(): void
    {
        static::created(function (Model $filho): void {
            $filho->reportarAoProtocoloDaTrilha();
        });

        static::updated(function (Model $filho): void {
            // Alterar um anexo ou comentario nao e novidade que mereca card proprio: o
            // vinculo ja foi anunciado quando ele apareceu. Conteudo do protocolo, sim
            // -- e o caso em que uma edicao real acabou de acontecer.
            if ($filho->acaoNaTrilhaDoProtocolo() === AcaoTrilha::Editado) {
                $filho->reportarAoProtocoloDaTrilha();
            }
        });
    }

    /**
     * O que foi acrescentado, do ponto de vista do dono: "um novo anexo",
     * "um comentario". Usado so quando a acao e Relacionado.
     */
    public function rotuloNaTrilhaDoProtocolo(): ?string
    {
        return null;
    }

    protected function reportarAoProtocoloDaTrilha(): void
    {
        app(RegistroDeAcao::class)->registrarNoProtocolo(
            $this->protocoloDaTrilhaClasse(),
            $this->protocoloDaTrilhaChave(),
            $this->acaoNaTrilhaDoProtocolo(),
            $this->rotuloNaTrilhaDoProtocolo(),
        );
    }
}
