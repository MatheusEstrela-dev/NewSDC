<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\RelatorioEtapa;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Models\DemandaCategoria;

/** Nome e a chave natural: o que ja foi cadastrado no catalogo e reaproveitado. */
final class ImportarCatalogo extends EtapaBase
{
    private string $origemAtual = 'chamados_categorias';

    public function nome(): string { return 'catalogo'; }
    protected function tabelaOrigem(): string { return $this->origemAtual; }
    protected function tabelaDestino(): string { return $this->origemAtual === 'assuntos' ? 'demanda_assuntos' : 'demanda_categorias'; }
    protected function temUpdatedAt(): bool { return false; }

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa
    {
        $this->origemAtual = 'chamados_categorias';
        $categorias = parent::executar($dryRun, $desde, $lote);
        $this->origemAtual = 'assuntos';
        $assuntos = parent::executar($dryRun, $desde, $lote);

        foreach (['lidos', 'importados', 'atualizados', 'ignorados', 'rejeitados'] as $campo) {
            $categorias->{$campo} += $assuntos->{$campo};
        }
        $categorias->motivos = array_merge($categorias->motivos, $assuntos->motivos);

        return $categorias;
    }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $nome = trim((string) $linha->nome);
        if ($nome === '') {
            return 'nome_vazio';
        }

        if ($this->origemAtual === 'chamados_categorias') {
            $categoria = DemandaCategoria::query()->whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first()
                ?? DemandaCategoria::create(['nome' => $nome, 'descricao' => $linha->descricao ?? null, 'ativo' => true]);

            return (int) $categoria->id;
        }

        $assunto = DemandaAssunto::query()->whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first() ?? new DemandaAssunto(['nome' => mb_substr($nome, 0, 150), 'ativo' => true]);
        $assunto->campos_dinamicos = MapaLegado::json($linha->campos_dinamicos ?? null);
        $automacao = MapaLegado::json($linha->form_automacao ?? null);
        $assunto->form_automacao = $automacao === [] ? null : $automacao;
        $assunto->save();

        return (int) $assunto->id;
    }
}
