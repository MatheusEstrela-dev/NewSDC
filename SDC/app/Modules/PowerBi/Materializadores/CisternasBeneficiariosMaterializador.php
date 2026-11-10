<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Services\BeneficiarioExportService;
use App\Modules\PowerBi\Support\CursorDeMaterializacao;
use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\PowerBi\Support\JanelaDaFonte;
use App\Modules\PowerBi\Support\MaterializadorBase;
use App\Modules\PowerBi\Support\ResultadoExecucao;
use App\Support\Database\ConexaoPowerBi;
use Generator;

/**
 * bi_cisternas.beneficiarios anonimizado, em estrategia completa: a linha
 * depende de municipio, comunidade e atendimentos de pipa, que mudam sem tocar
 * o updated_at do beneficiario (o incremental perderia essas mudancas). A base
 * e pequena (milhares) e o merge por hash so grava o que mudou.
 */
final class CisternasBeneficiariosMaterializador extends MaterializadorBase
{
    private const TABELA = 'beneficiarios';

    public function __construct(
        GravadorBi $gravador,
        private readonly BeneficiarioExportService $service,
        private readonly CisternasAnonimizador $anonimizador,
    ) {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'cisternas';
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
        $consulta = $this->service->consultarTodos(null, [], ConexaoPowerBi::FONTE)->reorder();

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'id') as $beneficiario) {
            yield $this->anonimizador->linha($beneficiario);
        }
    }

    public function chavesDaFonte(string $tabela): iterable
    {
        return $this->ids();
    }

    /** @return Generator<int, array{id: int}> */
    private function ids(): Generator
    {
        $consulta = CisternaBeneficiario::on(ConexaoPowerBi::FONTE)->select('id');
        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000), 'id') as $b) {
            yield ['id' => (int) $b->id];
        }
    }
}
