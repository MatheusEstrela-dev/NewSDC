<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\ViaturaSnapshot;

/**
 * Monta o payload e delega a formatacao a view de texto. Este service nao
 * conhece nenhum caractere de marcador: o formato vive em
 * resources/views/plantao/passagem-servico.txt.blade.php.
 *
 * O template usa {!! !!} e nao {{ }} de proposito: a saida e texto puro para
 * colar no WhatsApp, nao HTML. Com o escape do Blade, um `&` ou um apostrofo
 * digitado pelo plantonista chegaria ao grupo como `&amp;` / `&#039;`. Nao ha
 * vetor de XSS: RelatorioPassagemController devolve JSON e o preview renderiza
 * o texto dentro de <pre>{{ texto }}</pre>, que o Vue escapa.
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
                'anotacao' => $this->formatarAnotacao(
                    (bool) ($s->viatura?->exclusiva_sobreaviso ?? false),
                    $s->anotacao
                ),
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
            'periodo' => $plantao->tipoTurno?->labelCurto() ?? '',
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

    /**
     * `(Exclusiva Sobreaviso)` e derivado da flag booleana da viatura, nunca do
     * texto de `anotacao` (spec 3.3.1): `anotacao` e texto livre nesta release e
     * a Release 2 a substitui pela entidade de reservas, entao nada pode
     * depender do seu formato. Quando existem os dois, saem no mesmo parenteses,
     * a marca de sobreaviso primeiro, em ordem deterministica.
     */
    private function formatarAnotacao(bool $exclusivaSobreaviso, ?string $anotacao): string
    {
        $partes = [];

        if ($exclusivaSobreaviso) {
            $partes[] = 'Exclusiva Sobreaviso';
        }

        $texto = trim((string) $anotacao);

        if ($texto !== '') {
            $partes[] = $texto;
        }

        return $partes === [] ? '' : ' ('.implode(' - ', $partes).')';
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
