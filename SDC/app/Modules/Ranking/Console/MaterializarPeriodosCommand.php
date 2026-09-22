<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use App\Modules\Ranking\Enums\TipoPeriodo;
use App\Modules\Ranking\Services\PeriodoService;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

/**
 * Garante que as janelas de apuracao existem antes de qualquer escrita que
 * dependa delas.
 *
 * POR QUE ISTO E UM COMANDO E NAO UM EFEITO COLATERAL
 * Saldo, participante e snapshot referenciam periodo_id com FK. Se o periodo
 * fosse criado sob demanda dentro do snapshot, a virada do mes aconteceria
 * dentro de uma transacao de leitura, no pior momento possivel: varios workers
 * criando a mesma janela ao mesmo tempo, com o placar do dia 1 dependendo de
 * quem ganhou a corrida. Materializar antes torna a virada um passo explicito
 * do scheduler.
 *
 * Rode ANTES do snapshot na agenda. E idempotente por construcao (ON CONFLICT
 * DO NOTHING contra uq_ranking_periodos_chave), entao rodar todo dia, ou duas
 * vezes no mesmo minuto, nao produz efeito nenhum alem do primeiro.
 *
 * Uso:
 *   php artisan ranking:materializar-periodos
 *   php artisan ranking:materializar-periodos --ate=2027-03
 *   php artisan ranking:materializar-periodos --dry-run
 */
class MaterializarPeriodosCommand extends Command
{
    protected $signature = 'ranking:materializar-periodos
                            {--ate= : ultimo mes da janela (YYYY-MM ou data completa); padrao: mes corrente}
                            {--dry-run : mostra o que seria criado, sem escrever}';

    protected $description = 'Materializa os periodos (mes, ano e acumulado) do ranking para a janela indicada';

    public function __construct(private readonly PeriodoService $periodos)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $agora = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            $ate = $this->limiteSuperior($agora);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $janela = $this->periodos->janela($agora, $ate);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $seco = (bool) $this->option('dry-run');

        $this->components->info(sprintf(
            'Ranking - materializando %d periodo(s)%s',
            count($janela),
            $seco ? ' (dry-run, nada sera escrito)' : '',
        ));

        $linhas = [];
        $criados = 0;

        foreach ($janela as $descricao) {
            if ($seco) {
                $existia = $this->periodos->existePorChave($descricao['chave']);
                $situacao = $existia ? 'existente' : 'seria criado';
                $criados += $existia ? 0 : 1;
            } else {
                // existePorChave antes de resolver e so para o relatorio: quem
                // garante a nao duplicacao e a UNIQUE, nao esta checagem.
                $existia = $this->periodos->existePorChave($descricao['chave']);
                $this->periodos->resolverPeriodo($descricao['tipo'], $descricao['referencia']);
                $situacao = $existia ? 'existente' : 'criado';
                $criados += $existia ? 0 : 1;
            }

            $linhas[] = [
                $descricao['tipo']->value,
                $descricao['chave'],
                $this->instante($descricao['inicia_em']),
                $this->instante($descricao['termina_em']),
                $situacao,
            ];
        }

        $this->table(
            ['Tipo', 'Chave', 'inicia_em (UTC)', 'termina_em (UTC)', 'Situacao'],
            $linhas,
        );

        $this->components->twoColumnDetail(
            $seco ? 'Periodos que seriam criados' : 'Periodos criados',
            (string) $criados,
        );

        return self::SUCCESS;
    }

    /**
     * Interpreta --ate. `YYYY-MM` e o formato esperado no dia a dia; qualquer
     * data completa tambem serve. Sem a opcao, a janela e o mes corrente - o
     * uso normal no scheduler.
     */
    private function limiteSuperior(DateTimeImmutable $agora): DateTimeImmutable
    {
        $opcao = trim((string) ($this->option('ate') ?? ''));

        if ($opcao === '') {
            return $agora;
        }

        $fuso = new DateTimeZone(TipoPeriodo::FUSO_CALENDARIO);

        // Ancorar no dia 1 ao meio-dia local evita que 'YYYY-MM' sem dia caia
        // em horario de verao ou em mes de 31 dias durante o passeio da janela.
        if (preg_match('/^\d{4}-\d{2}$/', $opcao) === 1) {
            $data = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $opcao.'-01 12:00:00', $fuso);

            if ($data === false) {
                throw new RuntimeException("--ate invalido: '{$opcao}'.");
            }

            return $data;
        }

        try {
            return new DateTimeImmutable($opcao, $fuso);
        } catch (Throwable) {
            throw new RuntimeException("--ate invalido: '{$opcao}'. Use YYYY-MM ou uma data completa.");
        }
    }

    private function instante(?DateTimeImmutable $valor): string
    {
        return $valor?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP') ?? '(sem limite)';
    }
}
