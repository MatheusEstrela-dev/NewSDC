<?php

declare(strict_types=1);

namespace App\Support\Database\Geo;

/**
 * Fonte unica dos nomes de schema do geoespacial: <dominio>_<camada>.
 *
 * Dominio primeiro para o DBeaver/psql agruparem as camadas de um dominio
 * (ordem alfabetica) e para GRANT por dominio ser um padrao so. Dominio novo
 * entra como mais um case, sem renomear nada.
 */
enum DominioGeo: string
{
    case Meteorologia = 'meteorologia';
    case Sismo = 'sismo';
    case Hidro = 'hidro';
    case Camadas = 'camadas';

    /** Schema de referencia compartilhado (ex.: municipios copiados do sdc). */
    public const REFERENCIA = 'referencia';

    /** Controle da coleta: execucoes e cursores por fonte. */
    public function ingestao(): string
    {
        return $this->value.'_ingestao';
    }

    /** Payload bruto, imutavel. */
    public function bronze(): string
    {
        return $this->value.'_bronze';
    }

    public function silver(): string
    {
        return $this->value.'_silver';
    }

    /** Contrato de leitura: outro dominio so consome daqui. */
    public function gold(): string
    {
        return $this->value.'_gold';
    }

    /**
     * Nome qualificado de uma tabela numa camada: tabela('silver', 'sismos').
     */
    public function tabela(string $camada, string $tabela): string
    {
        // match fechado, e nao chamada dinamica: camada errada falha claro em
        // vez de cair em outro metodo do enum (schemas, tabela).
        $schema = match ($camada) {
            'ingestao' => $this->ingestao(),
            'bronze' => $this->bronze(),
            'silver' => $this->silver(),
            'gold' => $this->gold(),
            default => throw new \InvalidArgumentException("Camada geo desconhecida: {$camada}"),
        };

        return $schema.'.'.$tabela;
    }

    /**
     * @return list<string> os quatro schemas do dominio
     */
    public function schemas(): array
    {
        return [$this->ingestao(), $this->bronze(), $this->silver(), $this->gold()];
    }
}
