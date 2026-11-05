<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\PowerBi\Support\GravadorBi;
use App\Modules\Pae\Models\PaeEmpnto;
use App\Modules\Pae\Services\EmpreendimentoApiService;
use App\Support\Database\ConexaoPowerBi;

/**
 * bi_pae.empreendimentos em estrategia completa: municipio, empreendedor e
 * ultimo protocolo vem de outras tabelas e mudam sem tocar updated_at do
 * empreendimento. A tabela e pequena e o gravador so regrava o que mudou.
 */
final class PaeEmpreendimentosMaterializador extends MaterializadorCompletoBase
{
    public function __construct(GravadorBi $gravador, private readonly EmpreendimentoApiService $service)
    {
        parent::__construct($gravador);
    }

    public function modulo(): string
    {
        return 'pae';
    }

    protected function linhas(string $tabela): iterable
    {
        if ($tabela !== 'empreendimentos') {
            throw new \InvalidArgumentException("Tabela {$tabela} fora do catalogo de pae.");
        }

        $consulta = $this->service->consultaParaBi(ConexaoPowerBi::FONTE);

        foreach ($consulta->lazyById((int) config('powerbi.lote', 1000)) as $empreendimento) {
            /** @var PaeEmpnto $empreendimento */
            yield $this->service->linhaParaBi($empreendimento);
        }
    }
}
