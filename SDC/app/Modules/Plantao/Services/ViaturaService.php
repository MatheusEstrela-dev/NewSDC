<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ViaturaService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Viatura::query()
            ->with(['ultimoCondutor:id,name', 'movimentacaoAberta', 'reservaAgendada'])
            ->orderBy('prefixo')
            ->orderBy('placa');

        // Card "Reservadas": recorte proprio, e nao um valor de `status`, porque
        // reserva nao e estado fisico da viatura -- ela continua DISPONIVEL no
        // banco, escrito somente pelo MovimentacaoViaturaService.
        if (array_key_exists('reservada', $filters) && $filters['reservada'] !== null && $filters['reservada'] !== '') {
            $reservada = filter_var($filters['reservada'], FILTER_VALIDATE_BOOLEAN);

            $query->where(
                fn($q) => $reservada
                    ? $q->whereHas('reservaAgendada')
                    : $q->whereDoesntHave('reservaAgendada')
            );
        }

        if (!empty($filters['status'])) {
            // Cards agregados (ex.: "Indisponiveis" = MANUTENCAO+CEDIDA+INDISPONIVEL)
            // mandam uma lista separada por virgula; sem virgula, comportamento
            // de igualdade simples permanece inalterado.
            $statusFiltro = str_contains($filters['status'], ',')
                ? array_values(array_filter(array_map('trim', explode(',', $filters['status']))))
                : $filters['status'];

            if (is_array($statusFiltro)) {
                $query->whereIn('status', $statusFiltro);
            } else {
                $query->where('status', $statusFiltro);
            }
        }

        if (!empty($filters['localizacao'])) {
            $query->where('localizacao', $filters['localizacao']);
        }

        if (array_key_exists('ativo', $filters) && $filters['ativo'] !== null && $filters['ativo'] !== '') {
            $query->where('ativo', filter_var($filters['ativo'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'ilike', "%{$search}%")
                    ->orWhere('prefixo', 'ilike', "%{$search}%")
                    ->orWhere('modelo', 'ilike', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Viatura
    {
        return Viatura::with(['ultimoCondutor:id,name', 'movimentacaoAberta'])->find($id);
    }

    public function create(array $data): Viatura
    {
        return Viatura::create($data);
    }

    /**
     * Estado corrente da viatura (hodometro, combustivel e ultimo condutor) e
     * derivado do ledger de movimentacoes e escrito somente pelo
     * MovimentacaoViaturaService (spec 3.1). A edicao do cadastro nunca o toca:
     * o filtro abaixo e a fronteira, mesmo que algum request volte a aceitar os
     * campos por engano. `status` fica de fora do filtro de proposito - sem
     * escrita pelo CRUD os valores MANUTENCAO, CEDIDA e INDISPONIVEL seriam
     * inalcancaveis por qualquer caminho do sistema.
     */
    public const CAMPOS_SOMENTE_MOVIMENTACAO = [
        'hodometro_atual',
        'nivel_combustivel',
        'ultimo_condutor_id',
        'ultimo_condutor_nome',
    ];

    public function update(int $id, array $data): Viatura
    {
        $viatura = Viatura::findOrFail($id);
        $viatura->update(Arr::except($data, self::CAMPOS_SOMENTE_MOVIMENTACAO));

        return $viatura->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) Viatura::findOrFail($id)->delete();
    }

    /**
     * @return array{total:int,disponiveis:int,reservadas:int,em_transito:int,indisponiveis:int}
     */
    public function getStatistics(): array
    {
        $porStatus = Viatura::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $conta = static fn(StatusViatura ...$status): int => array_sum(
            array_map(fn(StatusViatura $s) => (int) ($porStatus[$s->value] ?? 0), $status)
        );

        // Viatura DISPONIVEL que ja tem reserva agendada NAO entra em
        // "Disponiveis": oferece-la como livre e o caminho para alguem sair com
        // ela e furar a reserva de quem chegar no horario. O status no banco
        // continua DISPONIVEL -- o recorte e de leitura, nao de escrita.
        $reservadas = Viatura::query()
            ->where('status', StatusViatura::DISPONIVEL->value)
            ->whereHas('reservaAgendada')
            ->count();

        return [
            'total' => array_sum($porStatus),
            'disponiveis' => max(0, $conta(StatusViatura::DISPONIVEL) - $reservadas),
            'reservadas' => $reservadas,
            'em_transito' => $conta(StatusViatura::EM_TRANSITO),
            'indisponiveis' => $conta(
                StatusViatura::MANUTENCAO,
                StatusViatura::CEDIDA,
                StatusViatura::INDISPONIVEL
            ),
        ];
    }
}
