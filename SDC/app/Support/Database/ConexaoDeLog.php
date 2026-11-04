<?php

declare(strict_types=1);

namespace App\Support\Database;

use App\Jobs\GravarLogPendenteJob;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ponto unico de acesso a conexao de log (config database.log_connection).
 *
 * Todos os tipos de log -- notificacoes, logs tecnicos, auditoria e historico
 * dos modulos -- passam por aqui ou pela trait UsaConexaoDeLog. Com a chave
 * desligada, o nome resolve para a conexao default e nada muda: o log segue na
 * mesma sessao e na mesma transacao das operacoes.
 *
 * Com a chave ligada o log tem sessao propria, e gravar DENTRO de uma transacao
 * critica aberta quebraria de tres jeitos: FK que nao enxerga a linha ainda nao
 * commitada, espera de lock que a propria transacao segura, e log fantasma
 * depois de rollback. Por isso gravar() adia a escrita para depois do commit da
 * conexao default (rollback descarta o log, que e o correto: o log reflete o
 * que de fato aconteceu) e, se a escrita falhar, manda para a fila em vez de
 * derrubar o modulo.
 *
 * Contrato:
 *  - nunca guardar a conexao de log em singleton ou propriedade: sob pool por
 *    coroutine ela vale so para a coroutine que a obteve;
 *  - nao fazer join entre tabela de log e tabela de modulo;
 *  - nao ler, dentro de uma transacao, o log que ela mesma gravou.
 */
final class ConexaoDeLog
{
    public static function nome(): string
    {
        // ?: e nao ??: DB_LOG_CONNECTION vazia no .env chega como string vazia.
        return (string) (config('database.log_connection') ?: config('database.default'));
    }

    /**
     * true quando o log tem sessao propria, diferente da conexao default.
     */
    public static function separada(): bool
    {
        return self::nome() !== (string) config('database.default');
    }

    public static function conexao(): Connection
    {
        return DB::connection(self::nome());
    }

    public static function tabela(string $tabela): Builder
    {
        return self::conexao()->table($tabela);
    }

    /**
     * Insert cru de log. Aceita uma linha ou uma lista de linhas.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $linhas
     */
    public static function inserir(string $tabela, array $linhas): void
    {
        $linhas = array_is_list($linhas) ? $linhas : [$linhas];

        self::gravar(
            fn () => self::tabela($tabela)->insert($linhas),
            fn (): array => [$tabela, $linhas],
        );
    }

    /**
     * Executa uma escrita de log respeitando a transacao critica.
     *
     * Devolve o retorno da escrita quando ela roda na hora, e null quando foi
     * adiada para o commit ou mandada para a fila.
     *
     * @param  callable(): mixed  $escrita
     * @param  callable(): array{0: string, 1: list<array<string, mixed>>}  $pendencia
     *         tabela e linhas para a retentativa, avaliadas so na falha (apos
     *         a tentativa o model ja tem timestamps e chave)
     */
    public static function gravar(callable $escrita, callable $pendencia): mixed
    {
        // Mesma sessao: escrita direta, atomica com a operacao, sem engolir
        // excecao. E o comportamento anterior a separacao.
        if (! self::separada()) {
            return $escrita();
        }

        // afterCommit roda na hora quando nao ha transacao aplicavel (inclusive
        // a transacao externa dos testes com DatabaseTransactions).
        $resultado = null;

        DB::connection()->afterCommit(function () use ($escrita, $pendencia, &$resultado): void {
            $resultado = self::executarOuEnfileirar($escrita, $pendencia);
        });

        return $resultado;
    }

    private static function executarOuEnfileirar(callable $escrita, callable $pendencia): mixed
    {
        try {
            return $escrita();
        } catch (Throwable $erro) {
            [$tabela, $linhas] = $pendencia();

            try {
                GravarLogPendenteJob::dispatch($tabela, $linhas);
            } catch (Throwable $falhaDaFila) {
                Log::error('Log perdido: conexao de log e fila indisponiveis', [
                    'tabela' => $tabela,
                    'erro' => $erro->getMessage(),
                    'erro_fila' => $falhaDaFila->getMessage(),
                ]);

                return null;
            }

            Log::warning('Log adiado para a fila apos falha na conexao de log', [
                'tabela' => $tabela,
                'erro' => $erro->getMessage(),
            ]);

            return null;
        }
    }
}
