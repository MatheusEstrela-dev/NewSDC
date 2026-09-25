<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\Exceptions\TetoDeLancamentoExcedido;
use DomainException;
use Illuminate\Database\Connection;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Publica nova versao de uma regra do catalogo a partir da vigente.
 *
 * POR QUE NAO UPDATE NO LUGAR
 * A regra vigente na COMPETENCIA do fato e a que vale, e replay de evento
 * antigo reaplica a versao antiga (RegraVigenteRepository). Editar a linha
 * vigente reescreveria retroativamente a tabela usada para fatos ja pontuados
 * e apagaria quem mudou o que. Toda alteracao fecha a vigente e abre versao+1.
 *
 * JANELA [vigente_de, vigente_ate)
 * A versao fechada termina exatamente no instante em que a nova comeca; com
 * fim exclusivo, o fato ocorrido na virada pertence so a versao nova.
 *
 * CUIDADO COM OCTANE: stateless. A conexao e resolvida por NOME a cada
 * chamada; ConnectionInterface por autowiring resolveria para a base
 * operacional.
 */
final class PublicarVersaoRegra
{
    /** Campos que o administrador pode alterar por publicacao. */
    private const CAMPOS = ['habilitada', 'aceita_bonus', 'bonus_percentual', 'pontos_base'];

    /** Motivo gravado quando a desabilitacao parte do administrador. */
    private const MOTIVO_MANUAL = 'manual_disable';

    /**
     * Fecha a versao vigente de `ruleKey` e publica versao+1 com `alteracoes`.
     *
     * @param array{habilitada?: bool, aceita_bonus?: bool, bonus_percentual?: int, pontos_base?: int} $alteracoes
     *
     * @return object|null A versao publicada, ou null quando nada muda em
     *                     relacao a vigente (no-op, nenhuma versao criada).
     */
    public function publicar(string $ruleKey, array $alteracoes, int $autorId): ?object
    {
        $alteracoes = $this->normalizar($alteracoes);

        return $this->conexao()->transaction(function (Connection $db) use ($ruleKey, $alteracoes, $autorId): ?object {
            // Lock consultivo por rule_key ANTES do FOR UPDATE. So o FOR UPDATE
            // nao basta: a publicacao concorrente que esperava o lock, ao ser
            // liberada, reavalia `vigente_ate IS NULL` na linha agora fechada,
            // nao encontra nada e responderia 404 em vez de publicar sobre a
            // versao nova. Com o lock consultivo a segunda transacao so le
            // depois do commit da primeira e enxerga a vigente atualizada.
            $db->statement(
                'SELECT pg_advisory_xact_lock(hashtext(?), hashtext(?))',
                ['ranking.regras', $ruleKey],
            );

            $vigente = $db->selectOne(
                'SELECT * FROM ranking.regras
                  WHERE rule_key = ? AND vigente_ate IS NULL
                  FOR UPDATE',
                [$ruleKey],
            );

            if ($vigente === null) {
                throw new RecordsNotFoundException("Regra '{$ruleKey}' sem versao vigente.");
            }

            $novos = $this->aplicar($vigente, $alteracoes);
            if ($novos === null) {
                return null;
            }

            // clock_timestamp e nao now(): now() e o inicio da transacao, e a
            // que esperou o lock teria um instante ANTERIOR ao vigente_de da
            // versao que acabou de ser publicada, gerando janela invertida.
            // Lido uma vez e reutilizado para que fechamento e abertura sejam
            // o mesmo instante.
            $agora = (string) $db->selectOne('SELECT clock_timestamp()::text AS agora')->agora;

            $invertida = $db->selectOne(
                'SELECT ?::timestamptz <= ?::timestamptz AS invertida',
                [$agora, $vigente->vigente_de],
            )->invertida;
            if ($this->booleano($invertida)) {
                // Versao com inicio futuro: fecha-la agora produziria fim antes
                // do inicio. Publicacao agendada nao e suportada por este fluxo.
                throw new DomainException("Versao vigente de '{$ruleKey}' ainda nao comecou.");
            }

            $db->update(
                'UPDATE ranking.regras SET vigente_ate = ?::timestamptz WHERE id = ?',
                [$agora, $vigente->id],
            );

            return $db->selectOne(
                'INSERT INTO ranking.regras
                    (rule_key, versao, modulo, familia, pontos_base, bonus_percentual,
                     aceita_bonus, exige_validacao, habilitada, motivo_desabilitada,
                     vigente_de, vigente_ate, publicado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::timestamptz, NULL, ?)
                 RETURNING *',
                [
                    $vigente->rule_key,
                    (int) $vigente->versao + 1,
                    $vigente->modulo,
                    $vigente->familia,
                    $novos['pontos_base'],
                    $novos['bonus_percentual'],
                    $novos['aceita_bonus'],
                    $this->booleano($vigente->exige_validacao),
                    $novos['habilitada'],
                    $novos['motivo_desabilitada'],
                    $agora,
                    $autorId,
                ],
            );
        });
    }

    private function conexao(): Connection
    {
        return DB::connection((string) config('ranking.conexao'));
    }

    /**
     * Recusa campo desconhecido e percentual fora do CHECK antes de abrir
     * transacao: erro de entrada nao deve custar lock.
     *
     * @return array<string, bool|int>
     */
    private function normalizar(array $alteracoes): array
    {
        $desconhecidos = array_diff(array_keys($alteracoes), self::CAMPOS);
        if ($desconhecidos !== []) {
            throw new InvalidArgumentException('Campos nao publicaveis: ' . implode(', ', $desconhecidos) . '.');
        }
        if ($alteracoes === []) {
            throw new InvalidArgumentException('Nenhuma alteracao informada.');
        }

        $normalizadas = [];
        foreach (['habilitada', 'aceita_bonus'] as $campo) {
            if (array_key_exists($campo, $alteracoes)) {
                $normalizadas[$campo] = (bool) $alteracoes[$campo];
            }
        }
        if (array_key_exists('bonus_percentual', $alteracoes)) {
            $percentual = (int) $alteracoes['bonus_percentual'];
            if ($percentual < 0 || $percentual > 100) {
                throw new InvalidArgumentException('bonus_percentual deve estar entre 0 e 100.');
            }
            $normalizadas['bonus_percentual'] = $percentual;
        }
        if (array_key_exists('pontos_base', $alteracoes)) {
            $base = (int) $alteracoes['pontos_base'];
            if ($base < 0) {
                throw new InvalidArgumentException('pontos_base nao pode ser negativo.');
            }
            $normalizadas['pontos_base'] = $base;
        }

        return $normalizadas;
    }

    /**
     * Estado da nova versao, ou null quando coincide com a vigente.
     *
     * O motivo so muda junto com `habilitada`: alterar o bonus de uma regra
     * desabilitada preserva o motivo original (ex.: falta de evidencia) em vez
     * de sobrescreve-lo como desabilitacao manual.
     *
     * @return array{habilitada: bool, aceita_bonus: bool, bonus_percentual: int, pontos_base: int, motivo_desabilitada: ?string}|null
     */
    private function aplicar(object $vigente, array $alteracoes): ?array
    {
        $atual = [
            'habilitada' => $this->booleano($vigente->habilitada),
            'aceita_bonus' => $this->booleano($vigente->aceita_bonus),
            'bonus_percentual' => (int) $vigente->bonus_percentual,
            'pontos_base' => (int) $vigente->pontos_base,
        ];
        $novos = array_replace($atual, $alteracoes);

        if ($novos === $atual) {
            return null;
        }

        // Mesma conta do ScoreCalculator: bonus truncado, e so se a regra aceita.
        $bonus = $novos['aceita_bonus'] ? intdiv($novos['pontos_base'] * $novos['bonus_percentual'], 100) : 0;
        $teto = (int) config('ranking.pontuacao.teto_por_lancamento');
        if ($novos['pontos_base'] + $bonus > $teto) {
            throw TetoDeLancamentoExcedido::para($novos['pontos_base'], $bonus, $teto);
        }

        $motivo = $vigente->motivo_desabilitada;
        if ($novos['habilitada'] !== $atual['habilitada']) {
            $motivo = $novos['habilitada'] ? null : self::MOTIVO_MANUAL;
        }

        return $novos + ['motivo_desabilitada' => $motivo];
    }

    /**
     * Booleano do PostgreSQL chega como bool, 't'/'f' ou '1'/'0' conforme o
     * driver; (bool) 'f' seria true e reabilitaria a regra em silencio.
     */
    private function booleano(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(strtolower(trim((string) $valor)), ['t', 'true', '1'], true);
    }
}
