<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Arquivos;

use App\Support\Database\ConexaoPowerBi;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/** controle.arquivos: registro dos Parquet publicados e retencao por dia. */
final class RegistroDeArquivos
{
    public static function disco(): string
    {
        return (string) config('powerbi.arquivos.disco', 'powerbi');
    }

    public function caminhoDoDia(string $modulo, string $tabela, CarbonInterface $dia): string
    {
        return sprintf('%s/%s/dt=%s/parte-0.parquet', $modulo, $tabela, $dia->format('Y-m-d'));
    }

    public function registrar(string $modulo, string $tabela, string $caminho, int $linhas, ?CarbonInterface $dadosAte): void
    {
        ConexaoPowerBi::escrita()->table('controle.arquivos')->updateOrInsert(
            ['caminho' => $caminho],
            [
                'modulo' => $modulo,
                'tabela' => $tabela,
                'linhas' => $linhas,
                'dados_ate' => $dadosAte,
                'formato' => 'parquet',
                'gerado_em' => now(),
            ]
        );
    }

    /**
     * Mantem so os N dias mais recentes da tabela; apaga arquivo e registro dos demais.
     *
     * @return int quantidade de arquivos removidos
     */
    public function aplicarRetencao(string $modulo, string $tabela): int
    {
        $manter = max(1, (int) config('powerbi.arquivos.retencao_dias', 7));
        $db = ConexaoPowerBi::escrita();
        $linhas = $db->table('controle.arquivos')
            ->where('modulo', $modulo)->where('tabela', $tabela)->where('formato', 'parquet')
            ->orderByDesc('caminho')->get(['id', 'caminho']);

        // O caminho contem dt=YYYY-MM-DD: ordem decrescente = dia mais novo primeiro.
        $dias = [];
        $apagar = [];
        foreach ($linhas as $l) {
            $dia = preg_match('/dt=(\d{4}-\d{2}-\d{2})\//', $l->caminho, $m) === 1 ? $m[1] : $l->caminho;
            $dias[$dia] = true;
            if (count($dias) > $manter) {
                $apagar[] = $l;
            }
        }

        $disco = Storage::disk(self::disco());
        foreach ($apagar as $l) {
            $disco->delete($l->caminho);
            $db->table('controle.arquivos')->where('id', $l->id)->delete();
        }

        return count($apagar);
    }

    /**
     * Apaga os arquivos sob demanda (csv/parquet de pedidos) mais velhos que a retencao.
     *
     * @return int quantidade de arquivos removidos
     */
    public function limparSobDemanda(): int
    {
        $disco = Storage::disk(self::disco());
        $limite = now()->subDays(max(1, (int) config('powerbi.arquivos.retencao_dias', 7)))->getTimestamp();
        $removidos = 0;

        foreach ($disco->allFiles((string) config('powerbi.arquivos.prefixo_sob_demanda', 'sob-demanda')) as $arquivo) {
            if ($disco->lastModified($arquivo) < $limite) {
                $disco->delete($arquivo);
                $removidos++;
            }
        }

        return $removidos;
    }
}
