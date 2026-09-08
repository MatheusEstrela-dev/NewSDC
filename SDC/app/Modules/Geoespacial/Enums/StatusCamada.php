<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Enums;

/**
 * Estado de uma camada e a maquina de transicao inteira, num lugar so.
 *
 *   PENDENTE  --aprovar--> APROVADA  --arquivar--> ARQUIVADA
 *   PENDENTE  --recusar--> RECUSADA  (terminal)
 *   ARQUIVADA --reativar-> APROVADA
 *
 * Nasceu quando ARQUIVADA entrou como quarto estado: com tres, comparar string
 * em cinco arquivos ainda passava; com quatro, "quem pode ir para onde" virou
 * regra de verdade, e regra espalhada em comparacao de string diverge na
 * primeira mudanca -- basta um lugar esquecer 'arquivada' para camada retirada
 * do mapa voltar a ser tratada como vigente.
 *
 * NAO existe DELETE. Camada aprovada esteve no mapa de plantao e pode ter
 * embasado decisao operacional: apagar a linha apagaria a prova de que a area
 * foi publicada, e por quanto tempo.
 *
 * O valor e o que esta na coluna `status` de silver.geo_camadas, restrito pelo
 * CHECK ck_silver_geo_camadas_status -- os dois precisam concordar, e e por isso
 * que valores() existe: a migration lista os mesmos quatro.
 */
enum StatusCamada: string
{
    case PENDENTE = 'pendente';
    case APROVADA = 'aprovada';
    case RECUSADA = 'recusada';
    case ARQUIVADA = 'arquivada';

    public function label(): string
    {
        return match ($this) {
            self::PENDENTE => 'Aguardando aprovacao',
            self::APROVADA => 'Aprovada',
            self::RECUSADA => 'Recusada',
            self::ARQUIVADA => 'Arquivada',
        };
    }

    /**
     * Esta no mapa operacional?
     *
     * E o mesmo predicado do `WHERE c.status = 'aprovada'` de
     * gold.geo_feicao_mapa. Ter isto aqui deixa o codigo perguntar em vez de
     * repetir a string do SQL.
     */
    public function noMapa(): bool
    {
        return $this === self::APROVADA;
    }

    /**
     * O REMETENTE pode editar metadado neste estado?
     *
     * Aprovada nao entra: a camada esta no mapa do estado, e deixar o municipio
     * trocar o nivel de "baixo" para "muito_alto" depois da aprovacao faria a
     * moderacao valer apenas para o primeiro instante da camada. Quem revisa
     * edita em qualquer estado -- ver Support\AcessoACamada.
     */
    public function editavelPeloRemetente(): bool
    {
        return $this === self::PENDENTE || $this === self::RECUSADA;
    }

    /**
     * Pode ser arquivada?
     *
     * So a aprovada. Pendente nao porque nem chegou ao mapa, e ja existe a acao
     * certa para ela: recusar, que exige motivo e avisa o remetente. Recusada
     * tambem nao, porque ja esta fora. Arquivar as duas criaria dois caminhos
     * para o mesmo resultado, com auditoria diferente.
     */
    public function arquivavel(): bool
    {
        return $this === self::APROVADA;
    }

    public function reativavel(): bool
    {
        return $this === self::ARQUIVADA;
    }

    /**
     * Le o que veio do banco.
     *
     * Default APROVADA, e nao PENDENTE, pelo mesmo motivo do DEFAULT da coluna:
     * as camadas estaduais anteriores a moderacao nasceram sem status, e
     * trata-las como pendentes as tiraria todas do mapa de uma vez.
     */
    public static function deBanco(int|string|null $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::APROVADA;
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(static fn (self $s): string => $s->value, self::cases());
    }
}
