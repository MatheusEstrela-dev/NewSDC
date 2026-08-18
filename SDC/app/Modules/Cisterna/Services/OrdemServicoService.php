<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Cisterna\DTOs\OrdemServicoDTO;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrdemServicoService
{
    public function listar(?int $loteId = null, int $porPagina = 25): LengthAwarePaginator
    {
        return CisternaOrdemServico::query()
            ->with('lote:id,nome')
            ->withCount('beneficiarios')
            ->when($loteId !== null, fn ($q) => $q->where('lote_id', $loteId))
            ->orderBy('nome')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function criar(OrdemServicoDTO $dto): CisternaOrdemServico
    {
        return CisternaOrdemServico::create($dto->toArray());
    }

    public function atualizar(CisternaOrdemServico $os, OrdemServicoDTO $dto): CisternaOrdemServico
    {
        $os->update($dto->toArray());

        return $os->fresh('lote');
    }

    /**
     * @throws ValidationException quando ha beneficiario alocado
     */
    public function deletar(CisternaOrdemServico $os): bool
    {
        $alocados = $os->beneficiarios()->count();

        if ($alocados > 0) {
            throw ValidationException::withMessages([
                'ordem_servico' => "Nao e possivel excluir: {$alocados} beneficiario(s) alocado(s) nesta ordem de servico.",
            ]);
        }

        return (bool) $os->delete();
    }

    /**
     * Historico do lote: quem entrou, quem saiu e o que mudou na propria OS.
     *
     * Le de `audit_logs`, a auditoria generica do projeto (`table_name`,
     * `row_id`, `old_values`/`new_values` jsonb). O padrao e o mesmo do
     * `UserManagementController::buildHistory()`.
     *
     * O legado fazia isso com whereJsonContains sobre valores_novos->os_id, e
     * precisava testar string E int porque o campo era gravado das duas formas
     * (CisternaOrdemServicoController.php:44-53). Aqui `ordem_servico_id` e
     * integer com FK; so precisa do cast, porque o operador ->> devolve texto.
     *
     * @return Collection<int, array{
     *     data: string,
     *     tipo: string,
     *     descricao: string,
     *     usuario: ?string,
     *     beneficiario_id: ?int,
     *     beneficiario_nome: ?string
     * }>
     */
    public function timeline(CisternaOrdemServico $os): Collection
    {
        $daOrdem = AuditLog::query()
            ->where('table_name', 'cisterna_ordens_servico')
            ->where('row_id', $os->getKey())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AuditLog $log): array => [
                'data' => $this->momento($log),
                'tipo' => 'ordem_servico',
                'descricao' => $this->descreverEventoDaOrdem($log),
                'usuario' => $log->user_id === null ? null : User::find($log->user_id)?->name,
                'beneficiario_id' => null,
                'beneficiario_nome' => null,
            ]);

        return $daOrdem->concat($this->movimentacoesDeBeneficiario($os))
            ->sortByDesc('data')
            ->values();
    }

    /**
     * Entradas e saidas de beneficiario nesta OS.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function movimentacoesDeBeneficiario(CisternaOrdemServico $os): Collection
    {
        $id = (string) $os->getKey();

        $logs = AuditLog::query()
            ->where('table_name', 'cisterna_beneficiarios')
            ->where(function ($q) use ($id): void {
                // O operador ->> devolve texto, por isso a comparacao com string.
                $q->whereRaw("new_values->>'ordem_servico_id' = ?", [$id])
                    ->orWhereRaw("old_values->>'ordem_servico_id' = ?", [$id]);
            })
            ->orderByDesc('created_at')
            ->get();

        if ($logs->isEmpty()) {
            return collect();
        }

        // Um SELECT para os nomes e um para os autores, em vez de um por linha.
        $nomes = CisternaBeneficiario::withTrashed()
            ->whereIn('id', $logs->pluck('row_id')->unique())
            ->pluck('nome', 'id');

        $usuarios = User::whereIn('id', $logs->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id');

        return $logs->map(function (AuditLog $log) use ($id, $nomes, $usuarios): array {
            $novo = $log->new_values['ordem_servico_id'] ?? null;
            $entrou = $novo !== null && (string) $novo === $id;
            $nome = $nomes[$log->row_id] ?? 'Beneficiario #'.$log->row_id;

            return [
                'data' => $this->momento($log),
                'tipo' => $entrou ? 'beneficiario_entrou' : 'beneficiario_saiu',
                'descricao' => $entrou
                    ? "{$nome} foi alocado nesta ordem de servico."
                    : "{$nome} saiu desta ordem de servico.",
                'usuario' => $log->user_id === null ? null : ($usuarios[$log->user_id] ?? null),
                'beneficiario_id' => (int) $log->row_id,
                'beneficiario_nome' => $nome,
            ];
        });
    }

    private function descreverEventoDaOrdem(AuditLog $log): string
    {
        // Vocabulario do CHECK de audit_logs.event: insert|update|delete|
        // login|logout. Nao sao os nomes dos eventos do Eloquent.
        return match ($log->event) {
            'insert' => 'Ordem de servico criada.',
            'delete' => 'Ordem de servico excluida.',
            default => 'Ordem de servico atualizada.',
        };
    }

    /**
     * `audit_logs.created_at` e timestamp sem timezone e o model nao o converte
     * para Carbon, entao o valor pode chegar como string.
     */
    private function momento(AuditLog $log): string
    {
        $valor = $log->created_at;

        if ($valor === null) {
            return '';
        }

        return $valor instanceof \DateTimeInterface
            ? $valor->format(\DATE_ATOM)
            : (string) $valor;
    }
}
