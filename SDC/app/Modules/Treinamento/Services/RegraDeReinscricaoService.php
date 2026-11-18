<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\DTOs\SituacaoDeReinscricao;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\LiberacaoInscricao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Regra das 3 inscricoes: a mesma pessoa (CPF) so pode se inscrever 3 vezes no
 * mesmo curso do catalogo. Contam todas as inscricoes nao concluidas e nao
 * excluidas; a 4a e recusada ate 1 ano depois da ultima que contou. O bloqueio
 * e calculado na hora; so a liberacao do gestor e gravada. A inscricao de
 * atualizacao para nova versao do curso nao conta.
 */
class RegraDeReinscricaoService
{
    public const LIMITE = 3;

    /** @param  Model|string  $pessoa  User, Cidadao ou CPF (com ou sem mascara) */
    public function situacao(int $cursoId, Model|string $pessoa): SituacaoDeReinscricao
    {
        $cpf = $this->cpfDe($pessoa);

        if ($cpf === '') {
            return new SituacaoDeReinscricao(0, self::LIMITE, false, null, []);
        }

        // Percorre em ordem de data: a terceira inscricao abre um bloqueio de 1
        // ano; uma inscricao depois do fim desse bloqueio recomeca a contagem
        // (tres tentativas novas), como depois de uma liberacao do gestor. Sem
        // isso, vencido o ano, a pessoa teria so UMA tentativa antes de novo
        // bloqueio, porque as tres antigas continuariam na conta.
        $contadas = [];
        $bloqueadoAte = null;

        foreach ($this->inscricoesQueContam($cursoId, $cpf) as $inscricao) {
            $data = Carbon::parse($inscricao['data']);

            if ($bloqueadoAte !== null && $data->gte($bloqueadoAte)) {
                $contadas = [];
                $bloqueadoAte = null;
            }

            $contadas[] = $inscricao;

            if ($bloqueadoAte === null && count($contadas) >= self::LIMITE) {
                $bloqueadoAte = $data->copy()->addYear();
            }
        }

        $contagem = count($contadas);
        $bloqueado = $bloqueadoAte !== null && now()->lt($bloqueadoAte);

        return new SituacaoDeReinscricao($contagem, self::LIMITE, $bloqueado, $bloqueadoAte, $contadas);
    }

    public function liberar(string $cpf, int $cursoId, User $gestor, string $motivo): LiberacaoInscricao
    {
        return LiberacaoInscricao::create([
            'cpf' => $this->somenteDigitos($cpf),
            'curso_id' => $cursoId,
            'liberado_por' => $gestor->getKey(),
            'motivo' => $motivo,
        ]);
    }

    /**
     * @return list<array{inscricao_id: int, treinamento_id: int, titulo: string, status: string, data: string}>
     */
    private function inscricoesQueContam(int $cursoId, string $cpf): array
    {
        $identidades = $this->identidadesDoCpf($cpf);

        if ($identidades[User::class] === [] && $identidades[Cidadao::class] === []) {
            return [];
        }

        $dataEfetiva = 'COALESCE(treinamento.inscricoes.data_inscricao, treinamento.inscricoes.created_at)';

        $query = Inscricao::query()
            ->join('treinamento.treinamentos as t', 't.id', '=', 'treinamento.inscricoes.treinamento_id')
            ->where('t.curso_id', $cursoId)
            ->whereNull('treinamento.inscricoes.concluido_em')
            // Atualizacao para nova versao nunca e tentativa (marca gravada, nao recalculada).
            ->whereNull('treinamento.inscricoes.atualizacao_de_inscricao_id')
            ->where(function ($q) use ($identidades) {
                foreach ($identidades as $tipo => $ids) {
                    if ($ids === []) {
                        continue;
                    }
                    $q->orWhere(fn ($w) => $w
                        ->where('treinamento.inscricoes.inscrito_type', $tipo)
                        ->whereIn('treinamento.inscricoes.inscrito_id', $ids));
                }
            })
            ->selectRaw("treinamento.inscricoes.id, treinamento.inscricoes.treinamento_id, t.titulo, treinamento.inscricoes.status, {$dataEfetiva} as data_efetiva");

        $liberadaEm = LiberacaoInscricao::query()
            ->where('cpf', $cpf)
            ->where('curso_id', $cursoId)
            ->max('created_at');

        if ($liberadaEm !== null) {
            $query->whereRaw("{$dataEfetiva} >= ?", [$liberadaEm]);
        }

        return $query->orderBy('data_efetiva')->get()
            ->map(fn ($linha) => [
                'inscricao_id' => (int) $linha->id,
                'treinamento_id' => (int) $linha->treinamento_id,
                'titulo' => (string) $linha->titulo,
                'status' => (string) ($linha->status instanceof \BackedEnum ? $linha->status->value : $linha->status),
                'data' => Carbon::parse($linha->data_efetiva)->toDateTimeString(),
            ])
            ->all();
    }

    /**
     * Todas as identidades do CPF: servidor (banco sdc) e cidadao (treinamento),
     * em duas consultas, sem JOIN entre bancos.
     *
     * @return array<class-string, list<int>>
     */
    private function identidadesDoCpf(string $cpf): array
    {
        return [
            User::class => User::query()->where('cpf', $cpf)->pluck('id')->map(fn ($id) => (int) $id)->all(),
            Cidadao::class => Cidadao::withTrashed()->where('cpf', $cpf)->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function cpfDe(Model|string $pessoa): string
    {
        return $this->somenteDigitos(is_string($pessoa) ? $pessoa : (string) ($pessoa->cpf ?? ''));
    }

    private function somenteDigitos(string $valor): string
    {
        return preg_replace('/\D/', '', $valor) ?? '';
    }
}
