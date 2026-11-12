<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Exceptions\PublicacaoBloqueada;
use App\Modules\Treinamento\Jobs\ReavaliarConclusoesDoCursoJob;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\Ordenacao;
use Illuminate\Http\UploadedFile;

/**
 * Montagem do conteudo do curso EAD (pastas e itens) pelo professor.
 */
class ConteudoCursoService
{
    public function __construct(
        private readonly ArquivoCursoService $arquivos,
        private readonly TreinamentoService $treinamentos,
        private readonly ProvaConteudoService $provas,
        private readonly VerificacaoPublicacaoService $verificacao,
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
            $this->reavaliarConclusoes($modulo->treinamento_id);
        });
    }

    /**
     * @param  list<int>  $ids
     */
    public function reordenarPastas(Treinamento $treinamento, array $ids): void
    {
        $ativos = Modulo::query()->porTreinamento($treinamento->id)->pluck('id')->all();
        Ordenacao::aplicar(Modulo::class, $ativos, $ids);
    }

    /**
     * @param  array<string, mixed>  $dados  tipo, titulo, descricao?, obrigatorio, total_paginas?, nota_minima?, max_tentativas?
     */
    public function criarItem(Modulo $modulo, array $dados, ?UploadedFile $arquivo): ModuloItem
    {
        $tipo = TipoItem::from($dados['tipo']);

        if ($tipo->exigeArquivo() && $arquivo === null) {
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
                'obrigatorio' => $tipo === TipoItem::PROVA || (bool) ($dados['obrigatorio'] ?? true),
                'total_paginas' => $tipo === TipoItem::PDF ? (int) $dados['total_paginas'] : null,
                'ordem' => ((int) $ultimo) + 1,
            ]);

            if ($tipo === TipoItem::PROVA) {
                $this->provas->criarParaItem($item, $dados);
            }

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
            'obrigatorio' => $item->tipo === TipoItem::PROVA || (bool) ($dados['obrigatorio'] ?? $item->obrigatorio),
            'total_paginas' => $item->tipo === TipoItem::PDF ? (int) $dados['total_paginas'] : null,
        ]);

        if ($item->tipo === TipoItem::PROVA && $item->prova !== null) {
            $this->provas->configurar($item->prova, $dados);
        }

        if ($arquivo !== null) {
            $this->arquivos->apagar($anterior);
        }

        if ($item->wasChanged('obrigatorio') && ! $item->obrigatorio) {
            $this->reavaliarConclusoes($item->modulo->treinamento_id);
        }

        return $item;
    }

    public function excluirItem(ModuloItem $item): void
    {
        $item->delete();
        $this->reavaliarConclusoes(Modulo::withTrashed()->whereKey($item->modulo_id)->value('treinamento_id'));
    }

    /**
     * @param  list<int>  $ids
     */
    public function reordenarItens(Modulo $modulo, array $ids): void
    {
        $ativos = ModuloItem::query()->where('modulo_id', $modulo->id)->pluck('id')->all();
        Ordenacao::aplicar(ModuloItem::class, $ativos, $ids);
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
            throw new PublicacaoBloqueada('Adicione ao menos um item ao curso antes de publicar.', ['Adicione ao menos um item ao curso antes de publicar.']);
        }

        $problemas = $this->verificacao->problemas($treinamento);
        if ($problemas !== []) {
            throw new PublicacaoBloqueada('O curso nao pode ser publicado. '.implode(' ', $problemas), $problemas);
        }

        $this->treinamentos->publicar($treinamento);

        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $treinamento->id,
            AcaoTrilha::Relacionado,
            'curso publicado',
        );
    }

    /**
     * A trilha pode ter ficado completa sem nenhuma transicao de item: os alunos
     * que faltavam so por isso concluem agora (job idempotente, depois do commit).
     */
    private function reavaliarConclusoes(int $treinamentoId): void
    {
        ReavaliarConclusoesDoCursoJob::dispatch($treinamentoId)->afterCommit();
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
}
