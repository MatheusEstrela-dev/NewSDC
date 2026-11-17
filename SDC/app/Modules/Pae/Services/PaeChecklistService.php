<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeChecklistResposta;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\Checklist\ChecklistGmg83;
use App\Modules\Pae\Support\TimelinePae;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checklist de analise GMG 83 por secao da pasta do protocolo.
 * Respostas sao mutaveis (uma linha por item); o historico fica na timeline.
 */
final class PaeChecklistService
{
    private const SECAO_CONSOLIDADA = 'relatorio';

    /** @return array<string, mixed> */
    public function secao(PaeProtocolo $protocolo, string $secao): array
    {
        $itens = ChecklistGmg83::daSecao($secao);
        $respostas = $this->respostas($protocolo, array_keys($itens));

        $grupos = [];
        foreach ($itens as $codigo => $item) {
            $grupos[$item['grupo']][] = $this->paraTela($codigo, $item, $respostas->get($codigo));
        }

        $dados = [
            'secao' => $secao,
            'titulo' => ChecklistGmg83::SECOES[$secao],
            'catalogo_versao' => ChecklistGmg83::VERSAO,
            'grupos' => array_map(
                static fn (string $titulo, array $lista): array => ['titulo' => $titulo, 'itens' => $lista],
                array_keys($grupos),
                array_values($grupos),
            ),
            'resumo' => $this->resumo($itens, $respostas),
        ];

        if ($secao === self::SECAO_CONSOLIDADA) {
            $dados['pendencias'] = $this->pendencias($protocolo);
        }

        return $dados;
    }

    /**
     * @param  list<array{codigo: string, resposta?: ?string, observacao?: ?string}>  $respostas
     */
    public function salvar(PaeProtocolo $protocolo, string $secao, array $respostas, User $user): void
    {
        $itens = ChecklistGmg83::daSecao($secao);
        $this->validar($itens, $respostas);

        DB::transaction(function () use ($protocolo, $secao, $respostas, $user): void {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            if ($locked->arquivado) {
                throw ValidationException::withMessages(['protocolo' => 'Protocolo arquivado: o checklist está disponível só para consulta.']);
            }

            foreach ($respostas as $resposta) {
                $observacao = trim((string) ($resposta['observacao'] ?? '')) ?: null;
                $valor = $resposta['resposta'] ?? null;
                $chave = ['codigo' => $resposta['codigo']];

                if ($valor === null && $observacao === null) {
                    $locked->checklistRespostas()->where($chave)->delete();
                    continue;
                }

                $locked->checklistRespostas()->updateOrCreate($chave, [
                    'resposta' => $valor,
                    'observacao' => $observacao,
                    'catalogo_versao' => ChecklistGmg83::VERSAO,
                    'updated_by' => $user->id,
                ]);
            }

            TimelinePae::registrar(
                $locked,
                'checklist_gmg83',
                sprintf('Checklist GMG 83 atualizado: %s (%d item(ns)).', ChecklistGmg83::SECOES[$secao], count($respostas)),
                $user,
            );
        });
    }

    /**
     * Itens marcados "Nao" em todas as secoes: base dos apontamentos ao empreendedor.
     *
     * @return list<array<string, mixed>>
     */
    public function pendencias(PaeProtocolo $protocolo): array
    {
        $catalogo = ChecklistGmg83::ITENS;
        $ordem = array_flip(array_keys($catalogo));

        return $protocolo->checklistRespostas()
            ->where('resposta', 'nao')
            ->get(['codigo', 'observacao'])
            ->filter(static fn (PaeChecklistResposta $r): bool => isset($catalogo[$r->codigo]))
            ->sortBy(static fn (PaeChecklistResposta $r): int => $ordem[$r->codigo])
            ->map(static fn (PaeChecklistResposta $r): array => [
                'codigo' => $r->codigo,
                'texto' => $catalogo[$r->codigo]['texto'],
                'secao' => $catalogo[$r->codigo]['secao'],
                'secao_titulo' => ChecklistGmg83::SECOES[$catalogo[$r->codigo]['secao']],
                'gravidade' => $catalogo[$r->codigo]['gravidade'],
                'artigos' => $catalogo[$r->codigo]['artigos'],
                'observacao' => $r->observacao,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $itens
     * @param  list<array<string, mixed>>  $respostas
     */
    private function validar(array $itens, array $respostas): void
    {
        $erros = [];
        foreach ($respostas as $indice => $resposta) {
            $codigo = $resposta['codigo'] ?? null;
            $item = $itens[$codigo] ?? null;
            if ($item === null) {
                $erros["respostas.{$indice}.codigo"] = 'Item que não pertence a esta seção do checklist.';
                continue;
            }

            $valor = $resposta['resposta'] ?? null;
            $permitidos = match ($item['tipo']) {
                'marcacao' => ['sim'],
                'texto' => [],
                default => ChecklistGmg83::RESPOSTAS,
            };
            if ($valor !== null && ! in_array($valor, $permitidos, true)) {
                $erros["respostas.{$indice}.resposta"] = "Resposta inválida para o item {$codigo}.";
            }
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }
    }

    /**
     * @param  list<string>  $codigos
     * @return Collection<string, PaeChecklistResposta>
     */
    private function respostas(PaeProtocolo $protocolo, array $codigos): Collection
    {
        return $protocolo->checklistRespostas()
            ->whereIn('codigo', $codigos)
            ->with('atualizador:id,name')
            ->get()
            ->keyBy('codigo');
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function paraTela(string $codigo, array $item, ?PaeChecklistResposta $resposta): array
    {
        return [
            'codigo' => $codigo,
            'texto' => $item['texto'],
            'base' => $item['base'],
            'gravidade' => $item['gravidade'],
            'artigos' => $item['artigos'],
            'tipo' => $item['tipo'],
            'resposta' => $resposta?->resposta,
            'observacao' => $resposta?->observacao,
            'atualizado_em' => $resposta?->updated_at?->toIso8601String(),
            'atualizado_por' => $resposta?->atualizador?->name,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $itens
     * @param  Collection<string, PaeChecklistResposta>  $respostas
     * @return array{total: int, respondidos: int, nao: int}
     */
    private function resumo(array $itens, Collection $respostas): array
    {
        // Os codigos tem ponto (B.1); Collection::only os leria como chave aninhada.
        $avaliaveis = array_filter($itens, static fn (array $item): bool => $item['tipo'] === 'resposta');
        $respondidas = $respostas->filter(
            static fn (PaeChecklistResposta $r): bool => isset($avaliaveis[$r->codigo]) && $r->resposta !== null,
        );

        return [
            'total' => count($avaliaveis),
            'respondidos' => $respondidas->count(),
            'nao' => $respondidas->where('resposta', 'nao')->count(),
        ];
    }
}
