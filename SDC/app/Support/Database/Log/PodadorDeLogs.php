<?php

declare(strict_types=1);

namespace App\Support\Database\Log;

use App\Support\Database\ConexaoDeLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

/**
 * Apaga, em lotes, o log que passou da retencao declarada em config/retencao.php.
 *
 * Cada lote e um DELETE ... WHERE chave IN (SELECT chave ... LIMIT lote) na
 * conexao de log, numa transacao propria e curta: segura pouco lock, gera WAL
 * em degraus e nunca abre transacao longa sobre a tabela. O SET LOCAL do
 * statement_timeout vale so para o lote e morre com ele, sem vazar para a
 * sessao (que, com pool, e reaproveitada).
 *
 * O podador nao conhece dominio: o que muda por tabela (coluna de idade,
 * predicados, reacao ao lote) vem da config.
 */
final class PodadorDeLogs
{
    public const PODADA = 'podada';

    public const SIMULADA = 'simulada';

    public const SEM_PODA = 'sem_poda';

    public const INTERROMPIDA = 'interrompida';

    /**
     * @param  list<string>  $tabelas  recorte; vazio = todas as da config
     * @param  int|null  $dias  sobrescreve os dias de toda tabela com poda
     * @param  bool  $simular  so conta, nao apaga
     * @param  int|null  $maxSegundos  orcamento de tempo; estourado, o resto fica para a proxima execucao
     * @return array<string, array{linhas: int, situacao: string}>
     *
     * @throws InvalidArgumentException tabela fora da config ou dias/lote invalidos
     */
    public function podar(
        array $tabelas = [],
        ?int $dias = null,
        ?int $lote = null,
        bool $simular = false,
        ?int $maxSegundos = null,
    ): array {
        $config = (array) config('retencao', []);
        $regras = (array) ($config['tabelas'] ?? []);

        $desconhecidas = array_diff($tabelas, array_keys($regras));

        if ($desconhecidas !== []) {
            throw new InvalidArgumentException(
                'Tabela fora de config/retencao.php: '.implode(', ', $desconhecidas)
            );
        }

        $lote ??= (int) ($config['lote'] ?? 1000);

        if ($lote < 1) {
            throw new InvalidArgumentException('O lote precisa ser maior que zero.');
        }

        $prazo = $maxSegundos === null ? null : microtime(true) + $maxSegundos;
        $resultado = [];

        foreach ($regras as $tabela => $regra) {
            if ($tabelas !== [] && ! in_array($tabela, $tabelas, true)) {
                continue;
            }

            $diasDaTabela = $this->dias((array) $regra, $dias, $config);

            if ($diasDaTabela === null) {
                $resultado[$tabela] = ['linhas' => 0, 'situacao' => self::SEM_PODA];

                continue;
            }

            $corte = now()->subDays($diasDaTabela);

            if ($simular) {
                $resultado[$tabela] = [
                    'linhas' => $this->candidatas($tabela, (array) $regra, $corte)->count(),
                    'situacao' => self::SIMULADA,
                ];

                continue;
            }

            $resultado[$tabela] = $this->apagar($tabela, (array) $regra, $corte, $lote, $config, $prazo);
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $regra
     * @param  array<string, mixed>  $config
     * @param  float|null  $prazo  instante (microtime) a partir do qual nao comeca lote novo
     * @return array{linhas: int, situacao: string}
     */
    private function apagar(
        string $tabela,
        array $regra,
        CarbonInterface $corte,
        int $lote,
        array $config,
        ?float $prazo,
    ): array {
        $chave = (string) ($regra['chave'] ?? 'id');
        $apos = isset($regra['apos']) ? $this->reacao((string) $regra['apos']) : null;
        $retornar = (array) ($regra['retornar'] ?? [$chave]);
        $pausaMs = max(0, (int) ($config['pausa_ms'] ?? 0));
        $timeoutMs = max(0, (int) ($config['statement_timeout_ms'] ?? 0));
        $total = 0;

        while (true) {
            if ($prazo !== null && microtime(true) >= $prazo) {
                return ['linhas' => $total, 'situacao' => self::INTERROMPIDA];
            }

            $linhas = $this->apagarLote($tabela, $regra, $corte, $chave, $lote, $timeoutMs, $apos !== null ? $retornar : null);
            $apagadas = is_array($linhas) ? count($linhas) : $linhas;
            $total += $apagadas;

            // Depois do commit do lote: quem reage ve o banco ja sem as linhas.
            if ($apos !== null && is_array($linhas) && $linhas !== []) {
                $apos->aposPodar($linhas);
            }

            if ($apagadas < $lote) {
                return ['linhas' => $total, 'situacao' => self::PODADA];
            }

            if ($pausaMs > 0) {
                usleep($pausaMs * 1000);
            }
        }
    }

    /**
     * Um lote, numa transacao propria. Devolve as linhas do RETURNING quando
     * ha colunas a retornar, ou a contagem do DELETE.
     *
     * @param  array<string, mixed>  $regra
     * @param  list<string>|null  $retornar
     * @return list<object>|int
     */
    private function apagarLote(
        string $tabela,
        array $regra,
        CarbonInterface $corte,
        string $chave,
        int $lote,
        int $timeoutMs,
        ?array $retornar,
    ): array|int {
        $conexao = ConexaoDeLog::conexao();
        $gramatica = $conexao->getQueryGrammar();
        $alvos = $this->candidatas($tabela, $regra, $corte)->select($chave)->limit($lote);

        $sql = sprintf(
            'delete from %s where %s in (%s)',
            $gramatica->wrapTable($tabela),
            $gramatica->wrap($chave),
            $alvos->toSql()
        );

        return $conexao->transaction(function (Connection $conexao) use ($sql, $alvos, $retornar, $timeoutMs, $gramatica): array|int {
            if ($timeoutMs > 0 && $conexao->getDriverName() === 'pgsql') {
                $conexao->statement(sprintf('SET LOCAL statement_timeout = %d', $timeoutMs));
            }

            if ($retornar === null) {
                return $conexao->delete($sql, $alvos->getBindings());
            }

            return $conexao->select($sql.' returning '.$gramatica->columnize($retornar), $alvos->getBindings(), false);
        });
    }

    /**
     * @param  array<string, mixed>  $regra
     */
    private function candidatas(string $tabela, array $regra, CarbonInterface $corte): Builder
    {
        $consulta = ConexaoDeLog::tabela($tabela)->where((string) $regra['coluna'], '<', $corte);

        foreach ((array) ($regra['nao_nulos'] ?? []) as $coluna) {
            $consulta->whereNotNull($coluna);
        }

        foreach ((array) ($regra['nulos'] ?? []) as $coluna) {
            $consulta->whereNull($coluna);
        }

        foreach ((array) ($regra['em'] ?? []) as $coluna => $valores) {
            $consulta->whereIn($coluna, (array) $valores);
        }

        return $consulta;
    }

    /**
     * Dias da tabela: 'dias' => null na regra desliga a poda (nem --dias
     * religa); senao vale a opcao, depois a regra, depois o padrao.
     *
     * @param  array<string, mixed>  $regra
     * @param  array<string, mixed>  $config
     */
    private function dias(array $regra, ?int $opcao, array $config): ?int
    {
        if (array_key_exists('dias', $regra) && $regra['dias'] === null) {
            return null;
        }

        $dias = (int) ($opcao ?? $regra['dias'] ?? $config['dias'] ?? 0);

        // Zero ou negativo apagaria log de hoje (ou do futuro): erro de config,
        // nunca intencao.
        if ($dias < 1) {
            throw new InvalidArgumentException('A retencao precisa ser de pelo menos 1 dia.');
        }

        return $dias;
    }

    private function reacao(string $classe): AposPoda
    {
        $reacao = app($classe);

        if (! $reacao instanceof AposPoda) {
            throw new InvalidArgumentException("{$classe} precisa implementar ".AposPoda::class.'.');
        }

        return $reacao;
    }
}
