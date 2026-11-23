<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\SituacaoParticipacao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;

/**
 * Regras da participacao no painel de indicadores. Fonte unica: todo bloco do
 * painel (e a satisfacao) parte de participacoes(), entao as regras abaixo
 * valem iguais em todo lugar. Nao ha coluna gravada: tudo e calculado ao vivo.
 *
 * Universo: inscricao APROVADA e nao excluida, em turma nao excluida e nao
 * CANCELADA. Cada linha e uma participacao (uma pessoa numa turma).
 *
 * Prazo da turma: o fim do ultimo evento do cronograma (MAX(COALESCE(fim,
 * inicio)) dos eventos nao excluidos); sem cronograma, a data_fim da turma;
 * sem nenhum dos dois, nao ha prazo.
 *
 * Situacao (particao: toda participacao cai em exatamente uma):
 * - CONCLUIDO: concluiu (concluido_em) ate o prazo, ou a turma nao tem prazo.
 * - DESISTENTE: o prazo ja passou e nao concluiu no prazo. Quem concluiu
 *   depois do prazo conta aqui: nao finalizou dentro do cronograma.
 * - EM_ANDAMENTO: nao concluiu e o prazo ainda nao passou (ou nao ha prazo).
 *
 * Mes de referencia (graficos mensais, filtro de ano): CONCLUIDO -> data da
 * conclusao; DESISTENTE -> o prazo (quando desistiu, para o painel);
 * EM_ANDAMENTO -> inicio da turma, ou a data da inscricao se nao houver.
 *
 * Municipio de referencia: o da turma; sem ele (EAD), o da pessoa na inscricao.
 *
 * O prazo sai de UMA agregacao do cronograma por turma (sem N+1) e as colunas
 * calculadas vem por LATERAL sem FROM, que o Postgres achata em expressoes:
 * cada bloco filtra e agrupa direto pelos aliases abaixo.
 */
final class RegrasDaParticipacao
{
    /** Data de nascimento: a faixa etaria e medida na data da inscricao. */
    private const NASCIMENTO = 'i.data_nascimento';

    /** Data da inscricao (created_at; data_inscricao e NOT NULL e cobre a falta). */
    private const DATA_INSCRICAO = 'COALESCE(i.created_at, i.data_inscricao)::date';

    public const PRAZO = 'pa.prazo';

    public const MUNICIPIO = 'pa.municipio_id';

    public const FAIXA_ETARIA = 'pa.faixa';

    public const SITUACAO = 'st.situacao';

    public const DATA_REFERENCIA = 'rf.data_referencia';

    /**
     * Participacoes do universo, com alias i (inscricao), t (turma) e as
     * colunas calculadas PRAZO, MUNICIPIO, FAIXA_ETARIA, SITUACAO e
     * DATA_REFERENCIA. Sem filtro do painel: quem chama acrescenta.
     */
    public static function participacoes(Connection $conexao): Builder
    {
        $cronograma = $conexao->table('treinamento.curso_cronogramas')
            ->select('treinamento_id')
            ->selectRaw('MAX(COALESCE(fim, inicio))::date AS fim')
            ->whereNull('deleted_at')
            ->groupBy('treinamento_id');

        return $conexao->table('treinamento.inscricoes as i')
            ->join('treinamento.treinamentos as t', 't.id', '=', 'i.treinamento_id')
            ->leftJoinSub($cronograma, 'cr', 'cr.treinamento_id', '=', 't.id')
            ->crossJoin(new Expression('LATERAL (SELECT ' . implode(', ', [
                'COALESCE(cr.fim, t.data_fim) AS prazo',
                'COALESCE(t.municipio_id, i.municipio_inscrito_id) AS municipio_id',
                FaixaEtaria::expressaoSql(self::NASCIMENTO, self::DATA_INSCRICAO) . ' AS faixa',
            ]) . ') AS pa'))
            ->crossJoin(new Expression('LATERAL (SELECT ' . self::situacao() . ' AS situacao) AS st'))
            ->crossJoin(new Expression('LATERAL (SELECT ' . self::dataReferencia() . ' AS data_referencia) AS rf'))
            ->where('i.status', StatusInscricao::APROVADA->value)
            ->whereNull('i.deleted_at')
            ->whereNull('t.deleted_at')
            ->where('t.status', '<>', StatusTreinamento::CANCELADO->value);
    }

    private static function situacao(): string
    {
        $prazo = self::PRAZO;
        $concluido = SituacaoParticipacao::CONCLUIDO->value;
        $desistente = SituacaoParticipacao::DESISTENTE->value;
        $andamento = SituacaoParticipacao::EM_ANDAMENTO->value;

        return 'CASE'
            . " WHEN i.concluido_em IS NOT NULL AND ({$prazo} IS NULL OR i.concluido_em::date <= {$prazo}) THEN '{$concluido}'"
            . " WHEN {$prazo} IS NOT NULL AND {$prazo} < CURRENT_DATE THEN '{$desistente}'"
            . " ELSE '{$andamento}' END";
    }

    private static function dataReferencia(): string
    {
        $situacao = self::SITUACAO;
        $concluido = SituacaoParticipacao::CONCLUIDO->value;
        $desistente = SituacaoParticipacao::DESISTENTE->value;

        return "CASE {$situacao}"
            . " WHEN '{$concluido}' THEN i.concluido_em::date"
            . " WHEN '{$desistente}' THEN " . self::PRAZO
            . ' ELSE COALESCE(t.data_inicio, ' . self::DATA_INSCRICAO . ') END';
    }
}
