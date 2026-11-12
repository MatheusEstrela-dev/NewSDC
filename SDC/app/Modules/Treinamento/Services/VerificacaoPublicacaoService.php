<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Support\Str;

/**
 * Pendencias que impedem publicar um curso EAD (spec 8.6): toda pasta termina
 * em uma prova, e toda prova tem questoes com exatamente uma alternativa
 * correta. Uma mensagem por problema, ja com o nome da pasta.
 */
class VerificacaoPublicacaoService
{
    /**
     * @return list<string>
     */
    public function problemas(Treinamento $treinamento): array
    {
        $pastas = Modulo::query()
            ->porTreinamento($treinamento->id)
            ->ordenados()
            ->with('itens')
            ->get();

        $provas = Prova::query()
            ->with('questoes.alternativas')
            ->whereIn('modulo_item_id', $pastas->pluck('itens')->flatten()->pluck('id'))
            ->get()
            ->keyBy('modulo_item_id');

        $problemas = [];
        foreach ($pastas as $pasta) {
            array_push($problemas, ...$this->problemasDaPasta($pasta, $provas));
        }

        return $problemas;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Prova>  $provas  por modulo_item_id
     * @return list<string>
     */
    private function problemasDaPasta(Modulo $pasta, $provas): array
    {
        $problemas = [];

        if ($pasta->itens->last()?->tipo !== TipoItem::PROVA) {
            $problemas[] = "A pasta \"{$pasta->titulo}\" precisa terminar com um item de prova.";
        }

        foreach ($pasta->itens->where('tipo', TipoItem::PROVA) as $item) {
            array_push($problemas, ...$this->problemasDaProva($pasta, $item, $provas->get($item->id)));
        }

        return $problemas;
    }

    /**
     * @return list<string>
     */
    private function problemasDaProva(Modulo $pasta, ModuloItem $item, ?Prova $prova): array
    {
        $rotulo = "Pasta \"{$pasta->titulo}\", prova \"{$item->titulo}\"";

        if ($prova === null || $prova->questoes->isEmpty()) {
            return ["{$rotulo}: a prova nao tem questoes."];
        }

        $problemas = [];
        foreach ($prova->questoes->values() as $posicao => $questao) {
            if ($this->corretas($questao) !== 1) {
                $numero = $posicao + 1;
                $trecho = Str::limit($questao->enunciado, 40);
                $problemas[] = "{$rotulo}: a questao {$numero} (\"{$trecho}\") precisa ter exatamente uma alternativa correta.";
            }
        }

        return $problemas;
    }

    private function corretas(ProvaQuestao $questao): int
    {
        return $questao->alternativas->where('correta', true)->count();
    }
}
