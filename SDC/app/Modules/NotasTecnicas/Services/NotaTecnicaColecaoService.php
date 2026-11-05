<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Models\User;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaFiltroDTO;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaAcesso;
use App\Modules\NotasTecnicas\Models\NotaTecnicaFavorito;
use App\Support\Database\ConexaoDeLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Colecoes pessoais do usuario: favoritas e recentes. Concentra o SQL dessas
 * colecoes para que lista, contadores e detalhe usem a mesma regra.
 */
final class NotaTecnicaColecaoService
{
    /** Quantas notas a colecao Recentes mostra. */
    public const LIMITE_RECENTES = 30;

    /** Predicado correlacionado com notas_tecnicas; espera 1 bind (user_id). */
    public function favoritaSql(): string
    {
        return 'exists(select 1 from nota_tecnica_favoritos f where f.user_id = ?'
            . ' and f.nota_tecnica_id = notas_tecnicas.id)';
    }

    /**
     * Alterna a favorita e devolve o estado final. O unique(user, nota) garante
     * a idempotencia mesmo com dois cliques simultaneos.
     */
    public function alternarFavorito(NotaTecnica $nota, User $user): bool
    {
        $removidas = NotaTecnicaFavorito::query()
            ->where('user_id', $user->id)
            ->where('nota_tecnica_id', $nota->id)
            ->delete();

        if ($removidas > 0) {
            return false;
        }

        NotaTecnicaFavorito::query()->insertOrIgnore([
            'user_id' => $user->id,
            'nota_tecnica_id' => $nota->id,
            'created_at' => now(),
        ]);

        return true;
    }

    public function ehFavorita(NotaTecnica $nota, User $user): bool
    {
        return NotaTecnicaFavorito::query()
            ->where('user_id', $user->id)
            ->where('nota_tecnica_id', $nota->id)
            ->exists();
    }

    /**
     * Upsert em uma query so: reabrir a nota apenas renova `acessado_em`.
     *
     * Vai pela conexao de log e, como upsert nao passa pelo save() da trait,
     * pelo ConexaoDeLog::gravar explicito. A retentativa da fila so insere: num
     * acesso repetido ela nao renova a data, o que e aceitavel para Recentes.
     */
    public function registrarAcesso(NotaTecnica $nota, User $user): void
    {
        $linha = ['user_id' => $user->id, 'nota_tecnica_id' => $nota->id, 'acessado_em' => now()];

        ConexaoDeLog::gravar(
            fn () => NotaTecnicaAcesso::query()->upsert([$linha], ['user_id', 'nota_tecnica_id'], ['acessado_em']),
            fn (): array => ['nota_tecnica_acessos', [$linha]],
        );
    }

    /**
     * Restringe a lista a colecao pedida. Recentes tambem assume a ordenacao
     * (mais recente primeiro) no lugar da ordem por codigo.
     *
     * @param  Builder<NotaTecnica>  $query
     */
    public function aplicar(Builder $query, ?string $colecao, User $user): void
    {
        if ($colecao === NotaTecnicaFiltroDTO::COLECAO_FAVORITAS) {
            $query->whereRaw($this->favoritaSql(), [$user->id]);

            return;
        }

        if ($colecao !== NotaTecnicaFiltroDTO::COLECAO_RECENTES) {
            return;
        }

        // O corte das 30 e por acesso do usuario; os demais filtros combinam
        // depois, sobre esse conjunto. As subqueries em nota_tecnica_acessos
        // (tabela de log) rodam embutidas na consulta de notas_tecnicas, pela
        // conexao default: mesmo banco, sem cruzar sessao.
        $query->whereIn('notas_tecnicas.id', NotaTecnicaAcesso::query()
            ->select('nota_tecnica_id')
            ->where('user_id', $user->id)
            ->orderByDesc('acessado_em')
            ->limit(self::LIMITE_RECENTES));

        $query->reorder()->orderByRaw(
            '(select a.acessado_em from nota_tecnica_acessos a where a.user_id = ?'
            . ' and a.nota_tecnica_id = notas_tecnicas.id) desc',
            [$user->id],
        );
    }

    /**
     * Total da colecao dentro do universo ja filtrado em $base (visibilidade e
     * status).
     *
     * @param  Builder<NotaTecnica>  $base
     */
    public function contar(Builder $base, string $colecao, User $user): int
    {
        $this->aplicar($base, $colecao, $user);

        return $base->reorder()->count();
    }
}
