<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Models\User;
use App\Modules\NotasTecnicas\Enums\NotaTecnicaStatus;
use App\Modules\NotasTecnicas\Models\NotaTecnicaLeitura;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * "Li e estou ciente" e o relatorio de quem leu cada versao.
 *
 * Publico-alvo: usuario ativo com notas_tecnicas.notas.view, por cargo ou
 * direto. Quem leu e saiu do publico (perdeu o acesso) continua contando como
 * leitura -- a ciencia aconteceu.
 */
final class NotaTecnicaLeituraService
{
    private const PERMISSAO_LEITURA = 'notas_tecnicas.notas.view';

    /**
     * Idempotente: repetir devolve a leitura ja registrada, com a data original.
     * So vale para a versao vigente de nota publicada -- ciencia de versao velha
     * ou de rascunho nao diz nada sobre o procedimento em vigor.
     */
    public function darCiencia(NotaTecnicaVersao $versao, User $user): NotaTecnicaLeitura
    {
        $nota = $versao->nota;

        if ($nota->status !== NotaTecnicaStatus::Publicada) {
            throw ValidationException::withMessages(['ciencia' => 'Só é possível dar ciência em nota publicada.']);
        }

        if ($nota->versaoAtual()->value('id') !== $versao->id) {
            throw ValidationException::withMessages(['ciencia' => 'Esta não é a versão vigente da nota.']);
        }

        return NotaTecnicaLeitura::query()->firstOrCreate(
            ['user_id' => $user->id, 'nota_tecnica_versao_id' => $versao->id],
            ['lida_em' => now()],
        );
    }

    /** @return array{lidas: int, publico: int, pendentes: int} */
    public function resumo(NotaTecnicaVersao $versao): array
    {
        return [
            'lidas' => $versao->leituras()->count(),
            'publico' => $this->publico()->count(),
            'pendentes' => $this->pendentesQuery($versao)->count(),
        ];
    }

    public function lidas(NotaTecnicaVersao $versao, ?string $busca, int $porPagina = 25): LengthAwarePaginator
    {
        return $this->filtrarBusca(
            User::query()
                ->join('nota_tecnica_leituras as l', 'l.user_id', '=', 'users.id')
                ->where('l.nota_tecnica_versao_id', $versao->id)
                ->select(['users.id', 'users.name', 'users.email', 'l.lida_em'])
                ->orderByDesc('l.lida_em'),
            $busca,
        )->paginate($porPagina)->withQueryString();
    }

    public function pendentes(NotaTecnicaVersao $versao, ?string $busca, int $porPagina = 25): LengthAwarePaginator
    {
        return $this->filtrarBusca(
            $this->pendentesQuery($versao)->select(['users.id', 'users.name', 'users.email'])->orderBy('users.name'),
            $busca,
        )->paginate($porPagina)->withQueryString();
    }

    /**
     * Linhas do CSV: lidas primeiro (com data), depois pendentes.
     *
     * @return iterable<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    public function linhasCsv(NotaTecnicaVersao $versao): iterable
    {
        yield ['Nome', 'E-mail', 'Situação', 'Lida em'];

        foreach ($versao->leituras()->with('usuario:id,name,email')->orderBy('lida_em')->lazy() as $leitura) {
            yield [
                (string) $leitura->usuario?->name,
                (string) $leitura->usuario?->email,
                'Leu',
                $leitura->lida_em->format('d/m/Y H:i'),
            ];
        }

        foreach ($this->pendentesQuery($versao)->select(['users.id', 'users.name', 'users.email'])->orderBy('users.name')->lazyById(500, 'users.id', 'id') as $user) {
            yield [(string) $user->name, (string) $user->email, 'Pendente', ''];
        }
    }

    /** @return Builder<User> */
    private function publico(): Builder
    {
        return User::permission(self::PERMISSAO_LEITURA)->where('users.active', true);
    }

    /** @return Builder<User> */
    private function pendentesQuery(NotaTecnicaVersao $versao): Builder
    {
        return $this->publico()->whereNotExists(fn ($q) => $q
            ->selectRaw('1')
            ->from('nota_tecnica_leituras as l')
            ->whereColumn('l.user_id', 'users.id')
            ->where('l.nota_tecnica_versao_id', $versao->id));
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private function filtrarBusca(Builder $query, ?string $busca): Builder
    {
        if ($busca === null || trim($busca) === '') {
            return $query;
        }

        $termo = '%' . trim($busca) . '%';

        return $query->where(fn (Builder $q) => $q
            ->where('users.name', 'ILIKE', $termo)
            ->orWhere('users.email', 'ILIKE', $termo));
    }
}
