<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\DTOs\EstadoTrilha;
use App\Modules\Treinamento\Enums\StatusEntrega;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ProjetoEntrega;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Visao do professor sobre os alunos do curso EAD. Nunca devolve CPF, e-mail
 * nem telefone (spec 8.6): so nome, origem e andamento.
 */
class AcompanhamentoService
{
    public const ORIGEM_SERVIDOR = 'Servidor';

    public const ORIGEM_CIDADAO = 'Cidadão';

    public function __construct(private readonly TrilhaService $trilha) {}

    /**
     * @return Collection<int, array{inscricao_id: int, nome: string, origem: string, percentual: float, onde_esta: string, travado: bool}>
     */
    public function alunos(Treinamento $treinamento, bool $soTravados = false): Collection
    {
        $inscricoes = Inscricao::query()
            ->porTreinamento($treinamento->id)
            ->where('status', StatusInscricao::APROVADA->value)
            ->orderBy('id')
            ->get();

        $nomes = $this->nomesDosInscritos($inscricoes);

        return $inscricoes
            ->map(function (Inscricao $inscricao) use ($nomes) {
                $estado = $this->trilha->estado($inscricao);

                return [
                    'inscricao_id' => $inscricao->id,
                    'nome' => $nomes[$this->chave($inscricao)] ?? '-',
                    'origem' => $inscricao->inscrito_type === User::class ? self::ORIGEM_SERVIDOR : self::ORIGEM_CIDADAO,
                    'percentual' => $estado->percentual,
                    'onde_esta' => $this->ondeEsta($estado),
                    'travado' => $this->estaTravado($estado),
                ];
            })
            ->when($soTravados, fn (Collection $c) => $c->filter(fn (array $a) => $a['travado']))
            ->values();
    }

    /**
     * Entregas ENVIADA, as mais antigas primeiro.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pendentes(Treinamento $treinamento): Collection
    {
        $entregas = $this->consultaPendentes($treinamento)
            ->join('treinamento.inscricoes as i', 'i.id', '=', 'projeto_entregas.inscricao_id')
            ->join('treinamento.modulo_itens as mi', 'mi.id', '=', 'projeto_entregas.modulo_item_id')
            ->join('treinamento.modulos as m', 'm.id', '=', 'mi.modulo_id')
            ->orderBy('projeto_entregas.id')
            ->get([
                'projeto_entregas.id',
                'projeto_entregas.arquivo_nome',
                'projeto_entregas.comentario',
                'projeto_entregas.created_at',
                'i.id as inscricao_id',
                'i.inscrito_type',
                'i.inscrito_id',
                'mi.titulo as item',
                'm.titulo as pasta',
            ]);

        $nomes = $this->nomesDosInscritos($entregas);

        return $entregas->map(fn ($e) => [
            'id' => (int) $e->id,
            'aluno' => $nomes[$this->chave($e)] ?? '-',
            'origem' => $e->inscrito_type === User::class ? self::ORIGEM_SERVIDOR : self::ORIGEM_CIDADAO,
            'pasta' => $e->pasta,
            'item' => $e->item,
            'arquivo_nome' => $e->arquivo_nome,
            'comentario' => $e->comentario,
            'enviada_em' => $e->created_at,
        ])->values();
    }

    /**
     * Entrega do curso informado; de outro curso e 404 (nao revela a existencia).
     */
    public function entregaDoCurso(Treinamento $treinamento, int $entregaId): ProjetoEntrega
    {
        return $this->entregasDoCurso($treinamento)->whereKey($entregaId)->firstOrFail();
    }

    public function contarPendentes(Treinamento $treinamento): int
    {
        return $this->consultaPendentes($treinamento)->count();
    }

    private function entregasDoCurso(Treinamento $treinamento): Builder
    {
        return ProjetoEntrega::query()->whereIn(
            'projeto_entregas.inscricao_id',
            Inscricao::query()->porTreinamento($treinamento->id)->select('id'),
        );
    }

    private function consultaPendentes(Treinamento $treinamento): Builder
    {
        return $this->entregasDoCurso($treinamento)->where('projeto_entregas.status', StatusEntrega::ENVIADA->value);
    }

    private function estaTravado(EstadoTrilha $estado): bool
    {
        foreach ($estado->pastas as $pasta) {
            foreach ($pasta->itens as $item) {
                if ($item->liberado && $item->item->tipo === TipoItem::PROJETO && $item->entrega === StatusEntrega::ENVIADA) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ondeEsta(EstadoTrilha $estado): string
    {
        if ($estado->proximoItemId === null) {
            return 'Concluiu todos os itens';
        }

        foreach ($estado->pastas as $pasta) {
            foreach ($pasta->itens as $item) {
                if ($item->item->id === $estado->proximoItemId) {
                    return $pasta->modulo->titulo.' - '.$item->item->titulo;
                }
            }
        }

        return 'Concluiu todos os itens';
    }

    /**
     * Nomes por (tipo, id): servidores em UMA consulta no banco `sdc` e
     * cidadaos em UMA no banco do Treinamento, sem join entre bancos.
     *
     * @param  Collection<int, object>  $linhas  com inscrito_type e inscrito_id
     * @return array<string, string>
     */
    private function nomesDosInscritos(Collection $linhas): array
    {
        $nomes = [];

        $ids = fn (string $tipo) => $linhas->where('inscrito_type', $tipo)->pluck('inscrito_id')->unique()->all();

        if (($idsUser = $ids(User::class)) !== []) {
            foreach (User::query()->whereIn('id', $idsUser)->pluck('name', 'id') as $id => $nome) {
                $nomes[User::class.':'.$id] = (string) $nome;
            }
        }
        if (($idsCidadao = $ids(Cidadao::class)) !== []) {
            foreach (Cidadao::query()->whereIn('id', $idsCidadao)->pluck('name', 'id') as $id => $nome) {
                $nomes[Cidadao::class.':'.$id] = (string) $nome;
            }
        }

        return $nomes;
    }

    private function chave(object $linha): string
    {
        return $linha->inscrito_type.':'.$linha->inscrito_id;
    }
}
