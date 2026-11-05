<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\PowerBi\Support\ConsultaNaFonte;
use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\MaterializadorBase;
use App\Modules\PowerBi\Support\ResultadoExecucao;
use App\Modules\Tdap\Services\TdapExportBiService;

/**
 * Exemplo minimo do nucleo: bi_tdap.viagens em estrategia completa (dimensoes
 * como prestador, lote ou municipio mudam sem tocar a viagem, entao o
 * incremental por updated_at da viagem perderia essas mudancas).
 */
final class TdapViagensMaterializador extends MaterializadorBase
{
    private const TABELA = 'viagens';

    public function __construct(GravadorBi $gravador, private readonly TdapExportBiService $service)
    {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'tdap';
    }

    public function materializar(JanelaDaFonte $janela, CursorDeMaterializacao $cursor): ResultadoExecucao
    {
        $definicao = $this->definicao(self::TABELA);

        $gravacao = $cursor->emTransacao(function () use ($definicao, $janela, $cursor) {
            $r = $this->gravador->gravarCompleta($definicao, $this->linhasAoVivo(self::TABELA));
            $cursor->avancar(self::TABELA, null, null, $janela);

            return $r;
        });

        return (new ResultadoExecucao())->registrar(self::TABELA, $gravacao);
    }

    public function linhasAoVivo(string $tabela): iterable
    {
        $consulta = ConsultaNaFonte::redirecionar($this->service->consultaBase());

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'v.id', 'viagem_id') as $linha) {
            yield $this->service->mapearLinha($linha);
        }
    }
}
