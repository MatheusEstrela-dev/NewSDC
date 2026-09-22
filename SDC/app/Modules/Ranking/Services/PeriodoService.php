<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Enums\EscopoPlacar;
use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Models\Participante;
use App\Modules\Ranking\Models\Periodo;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Materializa as janelas de apuracao (ranking.periodos) e a base de comparacao
 * do placar (ranking.participantes).
 *
 * POR QUE O PERIODO E UMA LINHA, E NAO UM CALCULO NA LEITURA
 * Saldo, snapshot e participante apontam para periodo_id. Se a janela fosse
 * recalculada a cada consulta, uma mudanca futura na regra de recorte (fuso,
 * primeiro dia do mes, feriado) reescreveria retroativamente a que periodo
 * pertence um lancamento ja gravado. Gravar os limites congela a decisao.
 *
 * FUSO: os instantes sao persistidos em UTC, mas o calendario e recortado em
 * America/Sao_Paulo (TipoPeriodo::FUSO_CALENDARIO) e o intervalo e sempre
 * [inicia_em, termina_em). Um fato de 31/12 as 21h BRT ocorre em 01/01 03:00
 * UTC e ainda assim pertence ao ano que terminou no Brasil - quem decide e o
 * calendario local, nao o relogio do servidor.
 *
 * ACUMULADO nao tem limite: ambas as colunas ficam nulas. Consulta de placar
 * acumulado nao filtra competencia.
 *
 * IDEMPOTENCIA: os dois metodos de escrita usam INSERT ... ON CONFLICT DO
 * NOTHING contra as UNIQUE do schema (uq_ranking_periodos_chave e
 * uq_ranking_participantes). Chamar duas vezes nao duplica e nao sobrescreve -
 * dois workers do scheduler podem rodar em paralelo sem corrida.
 *
 * CUIDADO COM OCTANE: este service nao guarda estado entre chamadas. Nada de
 * cache de periodo em propriedade: o worker sobrevive ao request e um periodo
 * memorizado atravessaria a virada do mes.
 */
final class PeriodoService
{
    /** Mesmo formato do RankingModel::$dateFormat, com offset explicito. */
    private const FORMATO_INSTANTE = 'Y-m-d H:i:sP';

    /**
     * Teto de seguranca da janela materializada. Nao e regra de negocio: e
     * freio contra `--ate` digitado errado gerando milhares de linhas.
     */
    public const MAX_MESES_JANELA = 120;

    /**
     * Devolve o periodo da referencia, criando-o quando ainda nao existe.
     *
     * A chave (TipoPeriodo::chave) e o identificador estavel e tambem a
     * constraint de unicidade, por isso a busca e a insercao usam a MESMA
     * chave - nunca uma comparacao por limites, que mudaria de resultado se o
     * recorte mudasse.
     */
    public function resolverPeriodo(TipoPeriodo $tipo, DateTimeImmutable $referencia): Periodo
    {
        $chave = $tipo->chave($referencia);

        $existente = $this->buscarPorChave($chave);

        if ($existente !== null) {
            return $existente;
        }

        [$inicio, $fim] = $tipo->limites($referencia);

        Periodo::query()->insertOrIgnore([
            'tipo' => $tipo->value,
            'chave' => $chave,
            'inicia_em' => $this->instante($inicio),
            'termina_em' => $this->instante($fim),
        ]);

        // Releitura obrigatoria: com ON CONFLICT DO NOTHING o insert pode nao
        // ter afetado linha alguma porque outro processo criou o periodo entre
        // a busca e a escrita. O vencedor da corrida e irrelevante - a linha e
        // a mesma.
        $periodo = $this->buscarPorChave($chave);

        if ($periodo === null) {
            throw new RuntimeException("Periodo '{$chave}' nao pode ser materializado.");
        }

        return $periodo;
    }

    /**
     * Inclui a entidade na base de comparacao do periodo.
     *
     * `regiao` e `tipo` sao CONGELADOS no momento do registro: reorganizacao
     * administrativa posterior (municipio que troca de regional, orgao que
     * muda de categoria) nao pode reescrever o ranking de um periodo ja
     * apurado. Por isso o conflito nao atualiza nada - a segunda chamada
     * devolve a linha original, com os valores de quando ela nasceu.
     *
     * Para mudar elegibilidade depois existe definirElegibilidade(), que e
     * explicito e nao toca em regiao/tipo.
     */
    public function registrarParticipante(
        Periodo $periodo,
        EscopoPlacar $escopo,
        int $entidadeId,
        ?string $regiao = null,
        ?string $tipo = null,
        bool $elegivel = true,
    ): Participante {
        if ($entidadeId <= 0) {
            throw new RuntimeException('Participante exige entidade_id positivo.');
        }

        $periodoId = (int) $periodo->getKey();

        $existente = $this->buscarParticipante($periodoId, $escopo, $entidadeId);

        if ($existente !== null) {
            return $existente;
        }

        Participante::query()->insertOrIgnore([
            'periodo_id' => $periodoId,
            'escopo' => $escopo->value,
            'entidade_id' => $entidadeId,
            'regiao' => $this->texto($regiao),
            'tipo' => $this->texto($tipo),
            'elegivel' => $elegivel,
        ]);

        $participante = $this->buscarParticipante($periodoId, $escopo, $entidadeId);

        if ($participante === null) {
            throw new RuntimeException(
                "Participante {$escopo->value}:{$entidadeId} nao pode ser registrado no periodo {$periodoId}."
            );
        }

        return $participante;
    }

    /**
     * Unica escrita autorizada sobre um participante ja registrado. Vinculo em
     * apuracao sai da classificacao sem sair do historico.
     *
     * @return bool false quando a entidade nao esta registrada no periodo.
     */
    public function definirElegibilidade(
        Periodo $periodo,
        EscopoPlacar $escopo,
        int $entidadeId,
        bool $elegivel,
    ): bool {
        return Participante::query()
            ->where('periodo_id', (int) $periodo->getKey())
            ->where('escopo', $escopo->value)
            ->where('entidade_id', $entidadeId)
            ->update(['elegivel' => $elegivel]) > 0;
    }

    /**
     * Descreve, SEM tocar no banco, os periodos que cobrem a janela indicada.
     *
     * Serve ao --dry-run e ao teste: o calendario e decidido aqui, a escrita
     * acontece em resolverPeriodo. A janela e percorrida mes a mes no fuso de
     * calendario; os anos e o acumulado saem por deduplicacao de chave.
     *
     * @return list<array{tipo: TipoPeriodo, referencia: DateTimeImmutable, chave: string, inicia_em: ?DateTimeImmutable, termina_em: ?DateTimeImmutable}>
     */
    public function janela(DateTimeImmutable $de, DateTimeImmutable $ate): array
    {
        $fuso = new DateTimeZone(TipoPeriodo::FUSO_CALENDARIO);

        $inicio = $de->setTimezone($fuso)->modify('first day of this month')->setTime(12, 0);
        $fim = $ate->setTimezone($fuso)->modify('first day of this month')->setTime(12, 0);

        // Janela invertida vira janela de um mes so, em vez de laco vazio: o
        // scheduler que passar --ate no passado ainda precisa do mes corrente.
        if ($fim < $inicio) {
            $fim = $inicio;
        }

        $descricoes = [];
        $cursor = $inicio;
        $meses = 0;

        while ($cursor <= $fim) {
            if (++$meses > self::MAX_MESES_JANELA) {
                throw new RuntimeException(sprintf(
                    'Janela de %s a %s excede o teto de %d meses.',
                    $inicio->format('Y-m'),
                    $fim->format('Y-m'),
                    self::MAX_MESES_JANELA,
                ));
            }

            foreach ([TipoPeriodo::Mes, TipoPeriodo::Ano] as $tipo) {
                $this->acumularDescricao($descricoes, $tipo, $cursor);
            }

            // 'first day of next month' a partir do dia 1 as 12h evita o
            // classico salto de 31/01 para 03/03 e o horario de verao.
            $cursor = $cursor->modify('first day of next month');
        }

        $this->acumularDescricao($descricoes, TipoPeriodo::Acumulado, $inicio);

        return array_values($descricoes);
    }

    public function existePorChave(string $chave): bool
    {
        return Periodo::query()->where('chave', $chave)->exists();
    }

    private function buscarPorChave(string $chave): ?Periodo
    {
        /** @var Periodo|null $periodo */
        $periodo = Periodo::query()->where('chave', $chave)->first();

        return $periodo;
    }

    private function buscarParticipante(int $periodoId, EscopoPlacar $escopo, int $entidadeId): ?Participante
    {
        /** @var Participante|null $participante */
        $participante = Participante::query()
            ->where('periodo_id', $periodoId)
            ->where('escopo', $escopo->value)
            ->where('entidade_id', $entidadeId)
            ->first();

        return $participante;
    }

    /**
     * @param  array<string, array{tipo: TipoPeriodo, referencia: DateTimeImmutable, chave: string, inicia_em: ?DateTimeImmutable, termina_em: ?DateTimeImmutable}>  $descricoes
     */
    private function acumularDescricao(array &$descricoes, TipoPeriodo $tipo, DateTimeImmutable $referencia): void
    {
        $chave = $tipo->chave($referencia);

        if (isset($descricoes[$chave])) {
            return;
        }

        [$inicio, $fim] = $tipo->limites($referencia);

        $descricoes[$chave] = [
            'tipo' => $tipo,
            'referencia' => $referencia,
            'chave' => $chave,
            'inicia_em' => $inicio,
            'termina_em' => $fim,
        ];
    }

    /**
     * Instante gravado sempre em UTC. Converter na borda evita que o offset do
     * processo (ou do container) vaze para a coluna.
     */
    private function instante(?DateTimeImmutable $valor): ?string
    {
        return $valor?->setTimezone(new DateTimeZone('UTC'))->format(self::FORMATO_INSTANTE);
    }

    /**
     * Texto vazio e ausencia de informacao, nao valor. Gravar '' faria a
     * regiao desconhecida virar uma regiao chamada "vazio" nos agrupamentos.
     */
    private function texto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpo = trim($valor);

        return $limpo === '' ? null : $limpo;
    }
}
