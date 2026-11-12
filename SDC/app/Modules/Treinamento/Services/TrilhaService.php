<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\EstadoItem;
use App\Modules\Treinamento\DTOs\EstadoPasta;
use App\Modules\Treinamento\DTOs\EstadoTrilha;
use App\Modules\Treinamento\DTOs\SituacaoProva;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProgressoItem;
use App\Modules\Treinamento\Models\ProgressoPasta;
use App\Modules\Treinamento\Models\ProjetoEntrega;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\CaminhosVideo;
use Illuminate\Support\Collection;

/**
 * Regra de liberacao da trilha: uma pasta so abre quando a anterior esta
 * concluida, e uma pasta concluida nunca reabre (grava em progresso_pastas).
 * Fonte unica de "o aluno pode ver este conteudo?" para os dois escopos.
 */
class TrilhaService
{
    public function __construct(private readonly SituacaoProvaService $provas) {}

    /**
     * @param  array<int, SituacaoProva>|null  $situacoes  provas da inscricao por modulo_item_id, quando quem
     *                                                      chama ja as carregou em lote; null consulta
     * @param  array<string, mixed>|null  $lote  conteudo do curso e progresso ja carregados por estadosEmLote()
     */
    public function estado(Inscricao $inscricao, ?array $situacoes = null, ?array $lote = null): EstadoTrilha
    {
        $situacoes ??= $this->provas->daInscricao($inscricao);
        $pastas = [];
        $total = 0;
        $concluidos = 0;
        $proximo = null;

        foreach ($this->percorrer($inscricao, $lote) as $p) {
            $estadoItens = [];
            foreach ($p['itens'] as $item) {
                $progresso = $p['progressos']->get($item->id);
                $concluido = $progresso?->status === StatusProgresso::CONCLUIDO;
                $entrega = $p['entregas']->get($item->id);
                $liberado = $p['liberados'][$item->id];

                if ($item->obrigatorio) {
                    $total++;
                    $concluidos += ($concluido || $p['gravada']) ? 1 : 0;
                }
                if ($proximo === null && $liberado && ! $concluido) {
                    $proximo = $item->id;
                }

                $estadoItens[] = new EstadoItem(
                    $item, $liberado, $progresso?->status, $progresso?->posicao ?? 0, $entrega?->status, $entrega?->parecer, $this->estadoVideo($item, $liberado), ($situacoes[$item->id] ?? null)?->toArray(),
                );
            }
            $pastas[] = new EstadoPasta($p['modulo'], $p['liberada'], $p['concluida'], $estadoItens);
        }

        $percentual = $total === 0 ? 0.0 : round(100 * $concluidos / $total, 1);

        return new EstadoTrilha($percentual, $proximo, $concluidos, $total, $pastas);
    }

    /**
     * Estado de varias inscricoes do MESMO curso com consultas em numero fixo
     * (pastas e itens uma vez; progresso, pastas gravadas e entregas em uma
     * consulta cada), para a lista de alunos do professor.
     *
     * @param  Collection<int, Inscricao>  $inscricoes
     * @param  array<int, array<int, SituacaoProva>>  $situacoesPorInscricao  de SituacaoProvaService::porInscricoes()
     * @return array<int, EstadoTrilha> por inscricao_id
     */
    public function estadosEmLote(Treinamento $treinamento, Collection $inscricoes, array $situacoesPorInscricao): array
    {
        $ids = $inscricoes->modelKeys();
        $lote = [
            'pastas' => $this->pastasDoCurso($treinamento->id),
            'progressos' => ProgressoItem::query()->whereIn('inscricao_id', $ids)->get()->groupBy('inscricao_id'),
            'gravadas' => ProgressoPasta::query()->whereIn('inscricao_id', $ids)->get(['inscricao_id', 'modulo_id'])->groupBy('inscricao_id'),
            'entregas' => ProjetoEntrega::query()->whereIn('inscricao_id', $ids)->orderByDesc('id')->get()->groupBy('inscricao_id'),
        ];

        $estados = [];
        foreach ($inscricoes as $inscricao) {
            $estados[$inscricao->id] = $this->estado($inscricao, $situacoesPorInscricao[$inscricao->id] ?? [], $lote);
        }

        return $estados;
    }

    /**
     * Previa do professor: tudo liberado, nada concluido, sem gravar progresso.
     */
    public function estadoPrevia(Treinamento $treinamento): EstadoTrilha
    {
        $pastas = [];
        $total = 0;
        $proximo = null;

        $modulos = Modulo::query()->porTreinamento($treinamento->id)->ordenados()->with('itens.video')->get();
        $regras = $this->provas->regrasDoCurso($treinamento->id);

        foreach ($modulos as $modulo) {
            $itens = [];
            foreach ($modulo->itens as $item) {
                $total += $item->obrigatorio ? 1 : 0;
                $proximo ??= $item->id;
                $itens[] = new EstadoItem($item, true, null, 0, null, null, $this->estadoVideo($item, true), ($regras[$item->id] ?? null)?->toArray());
            }
            $pastas[] = new EstadoPasta($modulo, true, false, $itens);
        }

        return new EstadoTrilha(0.0, $proximo, 0, $total, $pastas);
    }

    /**
     * Dados do video do item para o player; null fora de itens VIDEO. A URL do
     * master existe sempre que ha versao publicada, inclusive durante a troca
     * do arquivo (o aluno segue na versao atual). Item ainda bloqueado para a
     * inscricao nao recebe a URL (o /videos/auth ja a negaria).
     *
     * @return array{status: string, status_label: string, duracao_segundos: ?int, url_master: ?string}|null
     */
    private function estadoVideo(ModuloItem $item, bool $liberado): ?array
    {
        if ($item->tipo !== TipoItem::VIDEO) {
            return null;
        }

        $video = $item->video;
        if ($video === null) {
            return [
                'status' => VideoStatus::SEM_VIDEO->value,
                'status_label' => VideoStatus::SEM_VIDEO->getLabel(),
                'duracao_segundos' => null,
                'url_master' => null,
            ];
        }

        return [
            'status' => $video->status->value,
            'status_label' => $video->status->getLabel(),
            'duracao_segundos' => $video->duracao_segundos,
            'url_master' => $liberado && $video->temVersaoPublicada() ? CaminhosVideo::urlMaster($video->id, $video->hls_versao) : null,
        ];
    }

    public function itemLiberado(Inscricao $inscricao, ModuloItem $item): bool
    {
        foreach ($this->percorrer($inscricao) as $p) {
            if (array_key_exists($item->id, $p['liberados'])) {
                return $p['liberados'][$item->id];
            }
        }

        return false;
    }

    /**
     * Trilha inteira cumprida: inscricao aprovada, curso com ao menos um item
     * obrigatorio e todas as pastas ativas concluidas (pasta gravada em
     * progresso_pastas segue concluida mesmo com item novo, spec 8.7). Base do
     * criterio de conclusao por trilha.
     */
    public function concluida(Inscricao $inscricao): bool
    {
        $pastas = $this->percorrer($inscricao);
        $temObrigatorio = false;

        foreach ($pastas as $p) {
            if (! $p['liberada'] || ! $p['concluida']) {
                return false;
            }
            $temObrigatorio = $temObrigatorio || $p['itens']->contains('obrigatorio', true);
        }

        return $temObrigatorio;
    }

    /**
     * Grava a conclusao das pastas em ordem, parando na primeira que ainda
     * nao foi concluida. Idempotente e segura sob POSTs concorrentes (a
     * chave unica descarta o duplicado).
     */
    public function atualizarPastas(Inscricao $inscricao): void
    {
        foreach ($this->percorrer($inscricao) as $p) {
            if (! $p['liberada']) {
                return;
            }
            if ($p['gravada']) {
                continue;
            }
            if (! $p['concluida']) {
                return;
            }

            ProgressoPasta::query()->insertOrIgnore([
                'inscricao_id' => $inscricao->id,
                'modulo_id' => $p['modulo']->id,
                'concluida_em' => now(),
            ]);
        }
    }

    /**
     * Pastas e itens ativos em ordem, ja com liberacao e conclusao resolvidas.
     *
     * @param  array<string, mixed>|null  $lote  de estadosEmLote(); null carrega so esta inscricao
     * @return list<array{modulo: Modulo, itens: Collection<int, ModuloItem>, progressos: Collection<int, ProgressoItem>, entregas: Collection<int, ProjetoEntrega>, gravada: bool, concluida: bool, liberada: bool, liberados: array<int, bool>}>
     */
    private function percorrer(Inscricao $inscricao, ?array $lote = null): array
    {
        $pastas = $lote['pastas'] ?? $this->pastasDoCurso($inscricao->treinamento_id);

        // Em lote, quem nao tem linha nao aparece no groupBy: vazio, sem nova consulta.
        $progressos = ($lote === null
            ? ProgressoItem::query()->where('inscricao_id', $inscricao->id)->get()
            : ($lote['progressos'][$inscricao->id] ?? collect()))
            ->keyBy('modulo_item_id');
        $gravadas = ($lote === null
            ? ProgressoPasta::query()->where('inscricao_id', $inscricao->id)->get(['inscricao_id', 'modulo_id'])
            : ($lote['gravadas'][$inscricao->id] ?? collect()))
            ->pluck('modulo_id')->all();
        $entregas = ($lote === null
            ? ProjetoEntrega::query()->where('inscricao_id', $inscricao->id)->orderByDesc('id')->get()
            : ($lote['entregas'][$inscricao->id] ?? collect()))
            ->unique('modulo_item_id')
            ->keyBy('modulo_item_id');

        $liberada = $inscricao->status === StatusInscricao::APROVADA;
        $resultado = [];

        foreach ($pastas as $modulo) {
            $gravada = in_array($modulo->id, $gravadas, true);
            $obrigatoriosPendentes = $modulo->itens->where('obrigatorio', true)
                ->contains(fn (ModuloItem $i) => $progressos->get($i->id)?->status !== StatusProgresso::CONCLUIDO);
            $concluida = $gravada || ! $obrigatoriosPendentes;

            $resultado[] = [
                'modulo' => $modulo,
                'itens' => $modulo->itens,
                'progressos' => $progressos,
                'entregas' => $entregas,
                'gravada' => $gravada,
                'concluida' => $concluida,
                'liberada' => $liberada,
                'liberados' => $modulo->itens
                    ->mapWithKeys(fn (ModuloItem $i) => [$i->id => $this->itemAbertoNaPasta($i, $modulo->itens, $progressos, $liberada, $gravada)])
                    ->all(),
            ];
            $liberada = $liberada && $concluida;
        }

        return $resultado;
    }

    /** @return Collection<int, Modulo> pastas ativas em ordem, com os itens ativos e o video de cada um */
    private function pastasDoCurso(int $treinamentoId): Collection
    {
        return Modulo::query()->porTreinamento($treinamentoId)->ordenados()->with('itens.video')->get();
    }

    /**
     * Ponto unico de liberacao de um item dentro da pasta. Item comum abre com
     * a pasta; a PROVA so abre depois dos demais itens obrigatorios da pasta
     * concluidos (projeto so conta aprovado, spec 8.2). A pasta seguinte so
     * abre com a prova aprovada porque a prova e obrigatoria e so conclui ao
     * aprovar. Pasta ja gravada como concluida, ou prova ja concluida, nao volta
     * a travar quando um item obrigatorio novo entra depois.
     *
     * @param  Collection<int, ModuloItem>  $itensDaPasta
     * @param  Collection<int, ProgressoItem>  $progressos  por modulo_item_id
     */
    private function itemAbertoNaPasta(ModuloItem $item, Collection $itensDaPasta, Collection $progressos, bool $pastaLiberada, bool $pastaGravada): bool
    {
        if (! $pastaLiberada || $item->tipo !== TipoItem::PROVA || $pastaGravada || $progressos->get($item->id)?->status === StatusProgresso::CONCLUIDO) {
            return $pastaLiberada;
        }

        return ! $itensDaPasta
            ->filter(fn (ModuloItem $i) => $i->obrigatorio && $i->tipo !== TipoItem::PROVA)
            ->contains(fn (ModuloItem $i) => $progressos->get($i->id)?->status !== StatusProgresso::CONCLUIDO);
    }
}
