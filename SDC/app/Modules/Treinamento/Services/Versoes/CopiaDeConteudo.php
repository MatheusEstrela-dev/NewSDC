<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Versoes;

use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * Copia a trilha (pastas, itens, provas, questoes e alternativas) de um curso para
 * outro. Os arquivos e videos nao sao duplicados: o item copiado aponta para o mesmo
 * arquivo_path/video_id e guarda o item de origem em origem_item_id. A prova nao tem
 * coluna de origem propria; o vinculo e o do item.
 *
 * Deve rodar dentro de uma transacao aberta pelo chamador.
 */
class CopiaDeConteudo
{
    private const CAMPOS_PASTA = ['titulo', 'descricao', 'ordem', 'carga_horaria', 'data_prevista'];

    private const CAMPOS_ITEM = [
        'tipo', 'titulo', 'descricao', 'ordem', 'obrigatorio', 'arquivo_path', 'arquivo_nome',
        'arquivo_mime', 'arquivo_tamanho', 'total_paginas', 'video_id',
    ];

    public function copiar(Treinamento $origem, Treinamento $destino): void
    {
        $pastas = Modulo::query()
            ->where('treinamento_id', $origem->getKey())
            ->ordenados()
            ->with(['itens.prova.questoes.alternativas'])
            ->get();

        foreach ($pastas as $pasta) {
            $novaPasta = Modulo::query()->create(
                ['treinamento_id' => $destino->getKey()] + $pasta->only(self::CAMPOS_PASTA)
            );

            foreach ($pasta->itens as $item) {
                $this->copiarItem($item, $novaPasta);
            }
        }
    }

    private function copiarItem(ModuloItem $item, Modulo $pasta): void
    {
        $novo = ModuloItem::query()->create(
            ['modulo_id' => $pasta->getKey(), 'origem_item_id' => $item->getKey(), 'alterado_desde_copia' => false]
            + $item->only(self::CAMPOS_ITEM)
        );

        if ($item->prova !== null) {
            $this->copiarProva($item->prova, $novo);
        }
    }

    private function copiarProva(Prova $prova, ModuloItem $item): void
    {
        $nova = Prova::query()->create(
            ['modulo_item_id' => $item->getKey()] + $prova->only(['nota_minima', 'max_tentativas'])
        );

        foreach ($prova->questoes as $questao) {
            $nova->questoes()->create($questao->only(['enunciado', 'ordem']))
                ->alternativas()
                ->createMany(
                    $questao->alternativas
                        ->map(fn ($alt) => $alt->makeVisible('correta')->only(['texto', 'correta', 'ordem']))
                        ->all()
                );
        }
    }
}
