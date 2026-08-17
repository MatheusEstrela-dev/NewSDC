<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Console;

use App\Models\Municipio;
use App\Modules\Cisterna\Domain\Etl\LeitorRaw;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaBeneficiarios;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaComunidades;
use App\Modules\Cisterna\Domain\Etl\Refinadores\Refinador;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaLotes;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaMidia;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaNotificacoes;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaOrdensServico;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaVistoriaCedec;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaVistoriaCompdec;
use App\Modules\Cisterna\Domain\Etl\Refinadores\RefinaVistoriaFornecedor;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Services\NumeracaoInstalacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Etapa 2 do ETL: cisterna_legado_raw.doc jsonb -> tabelas do dominio.
 *
 * Idempotente por legacy_id: rodar duas vezes atualiza em vez de duplicar.
 * Cada linha e tratada isoladamente — um erro entra no cisterna_etl_log com
 * o payload de origem e a carga segue, em vez de abortar tudo.
 *
 * Mesmo padrao de AjudaHumanitaria\Console\RefinarLegadoAjuCommand.
 */
class RefinarCisternaLegadoCommand extends Command
{
    protected $signature = 'cisterna:refinar-legado
                            {--only= : Recursos separados por virgula: comunidades,lotes,os,beneficiarios,vistorias,notificacoes,midia}
                            {--chunk=500 : Linhas por lote}
                            {--dry-run : Nao escreve no dominio; registra no cisterna_etl_log o que faria}';

    protected $description = 'Refina cisterna_legado_raw para as tabelas do dominio Cisterna.';

    /**
     * Alias -> chaves internas.
     *
     * `--only=vistorias` roda as tres etapas, porque pedir uma etapa isolada
     * quase sempre e engano: o COMPDEC nao resolve nada sem a vistoria do
     * fornecedor no lugar.
     *
     * @var array<string, array<int, string>>
     */
    private const ALIASES = [
        'vistorias' => ['vistorias', 'vistorias_compdec', 'vistorias_cedec'],
    ];

    public function handle(LeitorRaw $leitor): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('DRY-RUN: nada sera escrito nas tabelas do dominio.');
        }

        $selecionados = $this->refinadoresSelecionados();

        if ($selecionados === []) {
            $this->error('Nenhum recurso reconhecido em --only. Conhecidos: '
                .implode(', ', array_keys($this->todosOsRefinadores())));

            return self::FAILURE;
        }

        foreach ($selecionados as $recurso => $refinador) {
            $total = $leitor->contar($refinador->tabelaLegado());

            if ($total === 0) {
                $this->warn("Sem linhas em cisterna_legado_raw para {$refinador->tabelaLegado()}. "
                    .'Rodar cisterna:extrair-legado primeiro.');

                continue;
            }

            $this->line("Refinando {$recurso} ({$total} linha(s))...");
            $barra = $this->output->createProgressBar($total);

            $leitor->porTabela(
                $refinador->tabelaLegado(),
                $chunk,
                function (array $doc, int $legacyId) use ($refinador, $dryRun, $barra): void {
                    try {
                        $refinador->refinar($doc, $legacyId, $dryRun);
                    } catch (Throwable $e) {
                        // Erro de uma linha nao derruba a carga inteira.
                        RegistroEtl::erro(
                            $refinador->recurso(),
                            $refinador->tabelaLegado(),
                            $legacyId,
                            'Excecao no refino: '.$e->getMessage(),
                            $doc,
                        );
                    }

                    $barra->advance();
                },
            );

            $barra->finish();
            $this->newLine();
        }

        $this->fecharCargas($selecionados, $dryRun);

        $this->newLine();
        $this->info('Resumo do cisterna_etl_log:');

        foreach (RegistroEtl::resumo() as $acao => $quantidade) {
            $this->line(sprintf('  %-10s %6d', $acao, $quantidade));
        }

        $this->newLine();
        $this->line('Detalhe dos erros:');
        $this->line('  artisan cisterna:refinar-legado --dry-run  # sem escrever no dominio');
        $this->line('  SELECT recurso, motivo, COUNT(*) FROM cisterna_etl_log');
        $this->line("  WHERE acao = 'error' GROUP BY 1, 2 ORDER BY 3 DESC;");

        return self::SUCCESS;
    }

    /**
     * Ajustes que dependem da carga ja estar no lugar, e nao de uma linha.
     *
     * @param  array<string, Refinador>  $selecionados
     */
    private function fecharCargas(array $selecionados, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        if (array_key_exists('vistorias', $selecionados)) {
            // Sem isto a sequence comeca em 1 e colide com todo numero de
            // instalacao importado do legado, cuja faixa real vai a 50.000.
            $maximo = app(NumeracaoInstalacaoService::class)->sincronizarSequenceComOMaximo();
            $this->line("Sequence de numero de instalacao alinhada em {$maximo}.");
        }

        if (array_key_exists('beneficiarios', $selecionados)) {
            $habilitados = $this->marcarMunicipiosHabilitados();
            $this->line("Municipios marcados com at_cisterna: {$habilitados}.");

            if ($habilitados === 0) {
                $this->warn('  Nenhum municipio marcado. Se cedec_municipio estiver vazia, '
                    .'rodar cedec:import-municipio antes -- sem ela o select de municipio '
                    .'das telas fica em branco.');
            }
        }
    }

    /**
     * Marca at_cisterna nos municipios que tem beneficiario importado.
     *
     * O flag mora em cedec_municipio, a ponte oficial de municipio do legado, e
     * chegou zerado no Postgres (spec 4.6.9-E). Sem isto o scope
     * Municipio::habilitadosCisterna() devolve lista vazia e todo select de
     * municipio das telas de cisterna fica em branco.
     *
     * O dado e derivavel: municipio habilitado e municipio que tem cadastro.
     */
    private function marcarMunicipiosHabilitados(): int
    {
        $codigos = DB::table('cisterna_beneficiarios as b')
            ->join('municipios as m', 'm.id', '=', 'b.municipio_id')
            ->whereNull('b.deleted_at')
            ->distinct()
            ->pluck('m.codigo_ibge');

        if ($codigos->isEmpty()) {
            return 0;
        }

        $marcados = DB::table('cedec_municipio')
            ->whereIn('Codmundv', $codigos)
            ->update(['at_cisterna' => 1]);

        // O scope cacheia por 24h no Redis e 300s por worker.
        Municipio::esquecerHabilitadosCisterna();

        return $marcados;
    }

    /**
     * @return array<string, Refinador>
     */
    private function todosOsRefinadores(): array
    {
        // A ordem importa e e a ordem das dependencias de FK:
        // comunidades, lotes e OS -> beneficiarios -> vistorias -> midia.
        // Dentro das vistorias, o COMPDEC depende da vistoria do fornecedor ja
        // existir, porque se liga a ela e nao ao beneficiario direto.
        return [
            'comunidades' => app(RefinaComunidades::class),
            'lotes' => app(RefinaLotes::class),
            'os' => app(RefinaOrdensServico::class),
            'beneficiarios' => app(RefinaBeneficiarios::class),
            'vistorias' => app(RefinaVistoriaFornecedor::class),
            'vistorias_compdec' => app(RefinaVistoriaCompdec::class),
            'vistorias_cedec' => app(RefinaVistoriaCedec::class),
            'notificacoes' => app(RefinaNotificacoes::class),
            'midia' => app(RefinaMidia::class),
        ];
    }

    /**
     * @return array<string, Refinador>
     */
    private function refinadoresSelecionados(): array
    {
        $todos = $this->todosOsRefinadores();
        $only = $this->option('only');

        if ($only === null || trim((string) $only) === '') {
            return $todos;
        }

        $pedidos = [];

        foreach (array_map('trim', explode(',', (string) $only)) as $pedido) {
            foreach (self::ALIASES[$pedido] ?? [$pedido] as $chave) {
                $pedidos[] = $chave;
            }
        }

        return array_filter(
            $todos,
            fn (string $recurso): bool => in_array($recurso, $pedidos, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
