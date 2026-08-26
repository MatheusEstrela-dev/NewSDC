<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\ViaturaSnapshot;

/**
 * Monta o payload e delega a formatacao a view de texto. Este service nao
 * conhece nenhum caractere de marcador: o formato vive em
 * resources/views/plantao/passagem-servico.txt.blade.php.
 */
class RelatorioPassagemService
{
    public function renderizar(Plantao $plantao): string
    {
        $rodape = config('plantao.relatorio.rodape');

        $viaturas = $plantao->snapshots
            ->filter(fn(ViaturaSnapshot $s) => (bool) $s->em_condicoes)
            ->map(fn(ViaturaSnapshot $s) => [
                'prefixo' => $s->prefixo,
                'placa' => $s->placa,
                'anotacao' => $this->formatarAnotacao($s->anotacao),
                'combustivel' => $s->nivel_combustivel?->label() ?? '',
                // Sem separador de milhar: o relatorio praticado escreve 112799.
                'hodometro' => (string) $s->hodometro,
                'alteracoes' => $this->textoOuPadrao($s->alteracoes, 'Sem alterações'),
                'condutor' => $s->ultimo_condutor_nome ?? '',
            ])
            ->values()
            ->all();

        // view() por nome nao resolve: o FileViewFinder so casa extensoes
        // inteiras registradas (blade.php, php, css, html) contra o nome
        // pontilhado, nunca uma extensao composta como .txt.blade.php. O
        // Factory::file() resolve o engine por sufixo do caminho, entao
        // reconhece ".txt.blade.php" como Blade sem precisar registrar
        // extensao no provider.
        return view()->file(resource_path('views/plantao/passagem-servico.txt.blade.php'), [
            'data' => $plantao->data?->format('d/m/Y') ?? '',
            'periodo' => $plantao->periodo?->labelCurto() ?? '',
            'plantonista' => $plantao->plantonista_nome ?? '',
            'plantonistaSaida' => $this->nuloSeVazio($plantao->plantonista_saida_nome),
            'localizacao' => $plantao->localizacao ?? '',
            'viaturas' => $viaturas,
            'contatosDiesel' => $rodape['contatos_diesel'],
            'linkBi' => $rodape['link_bi'],
            'dtt' => $rodape['dtt'],
            'gmg' => $rodape['gmg'],
            'ocorrencias' => $this->nuloSeVazio($plantao->ocorrencias_destaque),
        ])->render();
    }

    private function formatarAnotacao(?string $anotacao): string
    {
        $texto = trim((string) $anotacao);

        return $texto === '' ? '' : " ({$texto})";
    }

    private function textoOuPadrao(?string $valor, string $padrao): string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? $padrao : $texto;
    }

    private function nuloSeVazio(?string $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
