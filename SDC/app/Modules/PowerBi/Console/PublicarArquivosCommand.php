<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Console;

use App\Modules\PowerBi\Arquivos\LeitorTabelaBi;
use App\Modules\PowerBi\Arquivos\PublicadorParquetBi;
use App\Modules\PowerBi\Arquivos\RegistroDeArquivos;
use App\Modules\PowerBi\Support\CatalogoBi;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

final class PublicarArquivosCommand extends Command
{
    protected $signature = 'powerbi:publicar-arquivos
        {modulo? : Modulo (omita para todos os registrados)}
        {--tabela=* : Limita a estas tabelas}
        {--dia= : Dia da particao dt=YYYY-MM-DD (padrao: hoje)}';

    protected $description = 'Publica Parquet completo das tabelas bi_* no disco powerbi (dt=YYYY-MM-DD) e aplica a retencao';

    public function handle(
        CatalogoBi $catalogo,
        PublicadorParquetBi $publicador,
        LeitorTabelaBi $leitor,
        RegistroDeArquivos $registro,
    ): int {
        if (! ConexaoPowerBi::ativa()) {
            $this->error('sdc_powerbi desligado (DB_POWERBI_CONNECTION).');

            return self::FAILURE;
        }

        $dia = $this->option('dia') ? Carbon::parse((string) $this->option('dia')) : now();
        $modulos = $this->argument('modulo') ? [(string) $this->argument('modulo')] : $catalogo->modulos();
        $filtro = array_filter((array) $this->option('tabela'));
        $falhas = 0;

        foreach ($modulos as $modulo) {
            foreach (array_keys((array) config("powerbi.catalogo.{$modulo}", [])) as $tabela) {
                if ($filtro !== [] && ! in_array($tabela, $filtro, true)) {
                    continue;
                }
                $d = $catalogo->definicao($modulo, (string) $tabela);
                if ($d === null) {
                    $this->error("{$modulo}.{$tabela} fora do catalogo.");
                    $falhas++;

                    continue;
                }

                try {
                    $gerado = $publicador->gerarLocal($d);
                    try {
                        $caminho = $registro->caminhoDoDia($modulo, $d->nome, $dia);
                        $publicador->enviar($gerado['temporario'], $caminho);
                    } finally {
                        @unlink($gerado['temporario']);
                    }
                    $registro->registrar($modulo, $d->nome, $caminho, $gerado['linhas'], $leitor->dadosAte($d));
                    $removidos = $registro->aplicarRetencao($modulo, $d->nome);
                    $this->info(sprintf('%s.%s: %d linhas -> %s (retencao apagou %d)', $modulo, $d->nome, $gerado['linhas'], $caminho, $removidos));
                } catch (Throwable $e) {
                    $falhas++;
                    $this->error("{$modulo}.{$d->nome}: {$e->getMessage()}");
                }
            }
        }

        $this->line(sprintf('Sob demanda expirados removidos: %d', $registro->limparSobDemanda()));

        return $falhas === 0 ? self::SUCCESS : self::FAILURE;
    }
}
