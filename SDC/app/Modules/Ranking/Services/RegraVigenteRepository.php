<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\RegraVigente;
use App\Modules\Ranking\DTOs\ScoreRuleData;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Localiza no catalogo a versao da regra que vale na COMPETENCIA do fato.
 *
 * POR QUE COMPETENCIA E NAO "AGORA"
 * O catalogo e versionado e uma regra nunca e editada no lugar: publica-se
 * outra versao com nova vigencia. Buscar pela data de execucao do worker faria
 * um replay de evento de marco ser pontuado com a tabela de setembro - e o
 * reprocessamento do mesmo lote produziria numeros diferentes a cada vez.
 *
 * JANELA [vigente_de, vigente_ate)
 * Inicio inclusivo, fim EXCLUSIVO, igual a janela dos vinculos e a dos periodos
 * do placar. A troca de versao acontece num unico instante, e ele pertence a
 * versao que comeca. Com fim inclusivo, o fato ocorrido exatamente na virada
 * casaria com duas versoes e a escolha passaria a depender da ordenacao.
 *
 * REGRA DESABILITADA NAO E FILTRADA AQUI
 * `habilitada = false` e o estado de TODOS os marcos do catalogo enquanto a
 * evidencia da origem nao for comprovada. Esconder essas linhas faria o
 * ScoreCalculator decidir 'regra_nao_publicada' (marco inexistente) em vez de
 * 'regra_desabilitada' (marco conhecido e deliberadamente fora), e a transacao
 * ficaria sem regra_id - isto e, sem registro de contra qual regra a decisao
 * foi tomada. Quem decide o Zero e o calculo, nao a consulta.
 *
 * CUIDADO COM OCTANE: stateless. Nenhuma memoizacao em propriedade de
 * instancia: o catalogo muda por publicacao de versao e um cache de objeto
 * sobreviveria ao deploy da regra nova dentro do worker vivo.
 */
class RegraVigenteRepository
{
    public function __construct(
        private readonly string $conexao = RecordScoreTransaction::CONEXAO,

        /**
         * Valor de `exigeValidacao` enquanto ranking.regras NAO tiver a coluna
         * `exige_validacao`. O schema atual nao a possui (ver a migration do
         * modulo), e ScoreRuleData exige a informacao para decidir entre
         * Confirmada e Pendente.
         *
         * O padrao e `true` - o mais restritivo - de proposito: na duvida o
         * marco entra como Pendente e espera validacao humana, em vez de
         * creditar saldo que ninguem conferiu. Quando a coluna existir, ela
         * passa a prevalecer automaticamente e este padrao vira apenas o
         * fallback de linha antiga.
         */
        private readonly bool $exigeValidacaoPadrao = true,
    ) {}

    /**
     * Versao vigente de `ruleKey` na competencia, ou null quando o marco nao
     * tem regra publicada cobrindo aquele instante.
     */
    public function vigenteEm(string $ruleKey, DateTimeImmutable $competencia): ?RegraVigente
    {
        if (trim($ruleKey) === '') {
            return null;
        }

        // Instante normalizado em UTC com offset explicito. As colunas sao
        // timestamptz; texto sem fuso deixaria a comparacao a cargo do TimeZone
        // da sessao do Postgres e a fronteira exata da vigencia - justamente o
        // caso critico - dependeria de configuracao de servidor.
        $momento = $competencia->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');

        // SELECT * e deliberado: permite ler `exige_validacao` assim que a
        // coluna for adicionada, sem alterar esta consulta.
        $linha = DB::connection($this->conexao)->selectOne(
            'SELECT *
               FROM ranking.regras
              WHERE rule_key = ?
                AND vigente_de <= ?::timestamptz
                AND (vigente_ate IS NULL OR vigente_ate > ?::timestamptz)
              ORDER BY versao DESC, id DESC
              LIMIT 1',
            [$ruleKey, $momento, $momento],
        );

        if ($linha === null) {
            return null;
        }

        return new RegraVigente(
            id: (int) $linha->id,
            versao: (int) $linha->versao,
            regra: new ScoreRuleData(
                pontosBase: (int) $linha->pontos_base,
                habilitada: $this->booleano($linha->habilitada ?? null, false),
                aceitaBonus: $this->booleano($linha->aceita_bonus ?? null, false),
                exigeValidacao: $this->booleano($linha->exige_validacao ?? null, $this->exigeValidacaoPadrao),
                vigenteDesde: $this->instante($linha->vigente_de ?? null),
                vigenteAte: $this->instante($linha->vigente_ate ?? null),
            ),
        );
    }

    /**
     * Booleano do PostgreSQL, que chega ora como bool, ora como 't'/'f',
     * ora como '1'/'0', conforme o driver esteja emulando prepares ou nao.
     *
     * O cast direto (bool) NAO serve: (bool) 'f' e true, e uma regra
     * desabilitada passaria a creditar pontos silenciosamente.
     */
    private function booleano(mixed $valor, bool $padrao): bool
    {
        if ($valor === null) {
            return $padrao;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(strtolower(trim((string) $valor)), ['t', 'true', '1', 'y', 'yes', 'on'], true);
    }

    private function instante(mixed $valor): ?DateTimeImmutable
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof DateTimeImmutable) {
            return $valor;
        }

        $momento = date_create_immutable((string) $valor, new DateTimeZone('UTC'));

        if ($momento === false) {
            throw new RuntimeException("Vigencia ilegivel em ranking.regras: '{$valor}'.");
        }

        return $momento;
    }
}
