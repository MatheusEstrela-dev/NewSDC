<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Http\UploadedFile;

/**
 * Montagem do conteudo do curso EAD (pastas e itens) pelo professor.
 */
class ConteudoCursoService
{
    public function __construct(
        private readonly ArquivoCursoService $arquivos,
        private readonly TreinamentoService $treinamentos,
    ) {}

    /**
     * @param  array{titulo: string, descricao?: ?string, carga_horaria?: ?int}  $dados
     */
    public function criarPasta(Treinamento $treinamento, array $dados): Modulo
    {
        return ConexaoTreinamento::transacao(function () use ($treinamento, $dados) {
            $ultima = Modulo::query()->porTreinamento($treinamento->id)->max('ordem');

            return Modulo::query()->create([
                'treinamento_id' => $treinamento->id,
                'titulo' => $dados['titulo'],
                'descricao' => $dados['descricao'] ?? null,
                'carga_horaria' => (int) ($dados['carga_horaria'] ?? 0),
                'ordem' => ((int) $ultima) + 1,
            ]);
        });
    }

    /**
     * @param  array{titulo: string, descricao?: ?string, carga_horaria?: ?int}  $dados
     */
    public function atualizarPasta(Modulo $modulo, array $dados): Modulo
    {
        $modulo->update([
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? null,
            'carga_horaria' => (int) ($dados['carga_horaria'] ?? $modulo->carga_horaria),
        ]);

        return $modulo;
    }

    public function excluirPasta(Modulo $modulo): void
    {
        ConexaoTreinamento::transacao(function () use ($modulo) {
            $modulo->itens()->delete();
            $modulo->delete();
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function reordenarPastas(Treinamento $treinamento, array $ids): void
    {
        $ativos = Modulo::query()->porTreinamento($treinamento->id)->pluck('id')->all();
        $this->aplicarOrdem(Modulo::class, $ativos, $ids);
    }

    /**
     * @param  array<string, mixed>  $dados  tipo, titulo, descricao?, obrigatorio, total_paginas?
     */
    public function criarItem(Modulo $modulo, array $dados, ?UploadedFile $arquivo): ModuloItem
    {
        $tipo = TipoItem::from($dados['tipo']);

        if ($tipo === TipoItem::PDF && $arquivo === null) {
            throw new \DomainException('O item PDF exige um arquivo.');
        }
        $this->exigirPaginas($tipo, $dados);

        return ConexaoTreinamento::transacao(function () use ($modulo, $tipo, $dados, $arquivo) {
            $ultimo = ModuloItem::query()->where('modulo_id', $modulo->id)->max('ordem');

            $item = ModuloItem::query()->create([
                'modulo_id' => $modulo->id,
                'tipo' => $tipo,
                'titulo' => $dados['titulo'],
                'descricao' => $dados['descricao'] ?? null,
                'obrigatorio' => (bool) ($dados['obrigatorio'] ?? true),
                'total_paginas' => $tipo === TipoItem::PDF ? (int) $dados['total_paginas'] : null,
                'ordem' => ((int) $ultimo) + 1,
            ]);

            if ($arquivo !== null) {
                $item->update($this->arquivos->guardar($arquivo, $this->diretorio($modulo, $item)));
            }

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizarItem(ModuloItem $item, array $dados, ?UploadedFile $arquivo): ModuloItem
    {
        $dados['total_paginas'] ??= $item->total_paginas;
        $this->exigirPaginas($item->tipo, $dados);

        $anterior = $item->arquivo_path;
        $novo = $arquivo !== null
            ? $this->arquivos->guardar($arquivo, $this->diretorio($item->modulo, $item))
            : [];

        $item->update($novo + [
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'] ?? null,
            'obrigatorio' => (bool) ($dados['obrigatorio'] ?? $item->obrigatorio),
            'total_paginas' => $item->tipo === TipoItem::PDF ? (int) $dados['total_paginas'] : null,
        ]);

        if ($arquivo !== null) {
            $this->arquivos->apagar($anterior);
        }

        return $item;
    }

    public function excluirItem(ModuloItem $item): void
    {
        $item->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    public function reordenarItens(Modulo $modulo, array $ids): void
    {
        $ativos = ModuloItem::query()->where('modulo_id', $modulo->id)->pluck('id')->all();
        $this->aplicarOrdem(ModuloItem::class, $ativos, $ids);
    }

    /**
     * 404 se a pasta ou o item nao pertence ao curso da URL.
     */
    public function garantirDoCurso(Treinamento $treinamento, Modulo|ModuloItem $alvo): void
    {
        $treinamentoId = $alvo instanceof Modulo
            ? $alvo->treinamento_id
            : Modulo::withTrashed()->whereKey($alvo->modulo_id)->value('treinamento_id');

        abort_unless((int) $treinamentoId === (int) $treinamento->id, 404);
    }

    public function publicar(Treinamento $treinamento): void
    {
        $temItem = ModuloItem::query()
            ->whereIn('modulo_id', Modulo::query()->porTreinamento($treinamento->id)->select('id'))
            ->exists();

        if (! $temItem) {
            throw new \DomainException('Adicione ao menos um item ao curso antes de publicar.');
        }

        $this->treinamentos->publicar($treinamento);
    }

    private function diretorio(Modulo $modulo, ModuloItem $item): string
    {
        return "cursos/{$modulo->treinamento_id}/itens/{$item->id}";
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function exigirPaginas(TipoItem $tipo, array $dados): void
    {
        if ($tipo === TipoItem::PDF && (int) ($dados['total_paginas'] ?? 0) < 1) {
            throw new \DomainException('Informe o total de paginas do PDF.');
        }
    }

    /**
     * Grava ordem 1..n na sequencia recebida; a lista tem de ser exatamente o
     * conjunto de ids ativos.
     *
     * @param  class-string<Modulo|ModuloItem>  $model
     * @param  list<int|string>  $ativos
     * @param  list<int|string>  $ids
     */
    private function aplicarOrdem(string $model, array $ativos, array $ids): void
    {
        $ativos = array_map('intval', $ativos);
        $ids = array_map('intval', $ids);

        $igual = count($ids) === count($ativos)
            && count(array_unique($ids)) === count($ids)
            && array_diff($ids, $ativos) === [];

        if (! $igual) {
            throw new \DomainException('A lista de ordenacao nao corresponde aos itens atuais.');
        }

        ConexaoTreinamento::transacao(function () use ($model, $ids) {
            foreach ($ids as $posicao => $id) {
                $model::query()->whereKey($id)->update(['ordem' => $posicao + 1]);
            }
        });
    }
}
