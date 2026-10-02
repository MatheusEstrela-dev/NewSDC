<?php

declare(strict_types=1);

namespace App\Support\Permissoes;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Garante no banco o que config/permissions.php declara, sem depender do seeder.
 *
 * So acrescenta: cria permissao e cargo ausentes, completa coluna vazia e
 * concede par cargo -> permissao. Nunca revoga, nunca apaga, nunca sobrescreve
 * valor preenchido a mao (descricao, is_active, nome do cargo) e nao restaura
 * permissao excluida a mao. O super-admin recebe toda permissao nao excluida
 * do guard, inclusive as que estao no banco e nao no config, como no seeder.
 *
 * Dois modos de concessao:
 * - completo (manual): concede todo par do config que falta, inclusive o que
 *   foi revogado a mao no Permissionamento;
 * - somente novas (automatico, apos o migrate): so concede par cuja permissao
 *   ou cargo nasceu nesta execucao. Par antigo sem concessao fica como
 *   pendente no relatorio, para o boot nao desfazer revogacao manual.
 *
 * Cargo e unico por (name, guard_name), nao por slug: cargo do config e
 * achado pelo slug ou, sem slug, pelo nome. Nome igual com slug vazio recebe
 * o slug; com outro slug e conflito, sem concessao.
 *
 * Idempotente e seguro com processos concorrentes (insertOrIgnore sobre as
 * chaves unicas). Query builder, e nao os models: os eventos deles limpam o
 * cache do Spatie a cada save; aqui o cache e limpo uma vez, no fim.
 */
final class SincronizadorDePermissoes
{
    private const PERMISSOES = 'permissions';

    private const CARGOS = 'roles';

    private const CONCESSOES = 'role_has_permissions';

    private const SUPER_ADMIN = 'super-admin';

    /** Colunas que o sincronizador preenche numa linha existente quando estao vazias. */
    private const COLUNAS_COMPLETAVEIS = ['slug', 'description', 'group', 'module'];

    /** Default do schema para `group`: equivale a grupo nao informado. */
    private const GRUPO_PADRAO = 'general';

    private const LOTE_DE_INSERCAO = 1000;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CatalogoDePermissoes $catalogo,
        private readonly PermissionRegistrar $registrar,
    ) {}

    public function tabelasExistem(): bool
    {
        $schema = $this->db->connection()->getSchemaBuilder();

        foreach ([self::PERMISSOES, self::CARGOS, self::CONCESSOES] as $tabela) {
            if (! $schema->hasTable($tabela)) {
                return false;
            }
        }

        return true;
    }

    public function sincronizar(bool $simular = false, bool $somenteNovas = false): RelatorioDeSincronizacao
    {
        $conexao = $this->db->connection();

        if ($simular) {
            return $this->executar($conexao, true, $somenteNovas);
        }

        $relatorio = $conexao->transaction(
            fn (): RelatorioDeSincronizacao => $this->executar($conexao, false, $somenteNovas),
        );
        $this->registrar->forgetCachedPermissions();

        return $relatorio;
    }

    private function executar(ConnectionInterface $conexao, bool $simular, bool $somenteNovas): RelatorioDeSincronizacao
    {
        $guard = $this->catalogo->guard();
        $definidas = $this->catalogo->permissoes();
        $existentes = $conexao->table(self::PERMISSOES)
            ->where('guard_name', $guard)
            ->whereIn('name', array_keys($definidas))
            ->orderBy('id')
            ->get(['id', 'name', 'deleted_at', 'created_at', ...self::COLUNAS_COMPLETAVEIS])
            ->keyBy('name');

        $criadas = array_values(array_diff(array_keys($definidas), $existentes->keys()->all()));
        $excluidas = $existentes->whereNotNull('deleted_at')->keys()->values()->all();
        $completadas = $this->completarPermissoes($conexao, $existentes->whereNull('deleted_at'), $definidas, $simular);
        $cargos = $this->resolverCargos($conexao, $guard, $simular);

        if (! $simular) {
            $this->criarPermissoes($conexao, $guard, array_intersect_key($definidas, array_flip($criadas)));
        }

        [$novas, $pendentes] = $this->conceder($conexao, $guard, $criadas, $excluidas, $cargos, $simular, $somenteNovas);

        return new RelatorioDeSincronizacao(
            simulado: $simular,
            somenteNovas: $somenteNovas,
            permissoesCriadas: $criadas,
            permissoesCompletadas: $completadas,
            permissoesExcluidas: $excluidas,
            cargosCriados: $cargos['criados'],
            cargosCompletados: $cargos['completados'],
            cargosEmConflito: $cargos['conflitos'],
            concessoesNovas: $novas,
            concessoesPendentes: $pendentes,
        );
    }

    /** @param  array<string, array<string, mixed>>  $definicoes */
    private function criarPermissoes(ConnectionInterface $conexao, string $guard, array $definicoes): void
    {
        $agora = now();
        $linhas = array_map(static fn (array $definicao): array => [
            'name' => $definicao['slug'],
            'guard_name' => $guard,
            ...$definicao,
            'is_active' => true,
            'created_at' => $agora,
            'updated_at' => $agora,
        ], array_values($definicoes));

        $this->inserirEmLotes($conexao, self::PERMISSOES, $linhas);
    }

    /**
     * @param  Collection<string, object>  $existentes  em ordem de id
     * @param  array<string, array<string, mixed>>  $definidas
     * @return list<string>
     */
    private function completarPermissoes(ConnectionInterface $conexao, Collection $existentes, array $definidas, bool $simular): array
    {
        $completadas = [];

        foreach ($existentes as $nome => $linha) {
            $faltantes = $this->colunasFaltantes($linha, $definidas[$nome]);
            if ($faltantes === []) {
                continue;
            }

            $completadas[] = (string) $nome;
            if (! $simular) {
                $conexao->table(self::PERMISSOES)->where('id', $linha->id)->update([...$faltantes, 'updated_at' => now()]);
            }
        }

        return $completadas;
    }

    /**
     * @param  array<string, mixed>  $definicao
     * @return array<string, mixed>
     */
    private function colunasFaltantes(object $linha, array $definicao): array
    {
        $faltantes = [];

        foreach (self::COLUNAS_COMPLETAVEIS as $coluna) {
            $atual = $linha->{$coluna};
            $vazia = $atual === null || $atual === '' || ($coluna === 'group' && $atual === self::GRUPO_PADRAO);
            if ($vazia && $definicao[$coluna] !== $atual) {
                $faltantes[$coluna] = $definicao[$coluna];
            }
        }

        if ($linha->created_at === null) {
            $faltantes['created_at'] = now();
        }

        return $faltantes;
    }

    /**
     * Acha cada cargo do config pelo slug ou, sem slug, pelo nome (a chave
     * unica). Linha excluida conta como existente: cargo excluido a mao
     * continua excluido. Simulacao e execucao real decidem igual.
     *
     * @return array{criados: list<string>, completados: list<string>, conflitos: array<string, string>, ids: array<string, int>}
     */
    private function resolverCargos(ConnectionInterface $conexao, string $guard, bool $simular): array
    {
        $cargos = $this->catalogo->cargos();
        $linhas = $conexao->table(self::CARGOS)
            ->where('guard_name', $guard)
            ->where(fn ($q) => $q->whereIn('slug', array_keys($cargos))->orWhereIn('name', array_column($cargos, 'name')))
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'deleted_at']);
        $porSlug = $linhas->whereNotNull('slug')->unique('slug')->keyBy('slug');
        $porNome = $linhas->unique('name')->keyBy('name')->all();

        $resolucao = ['criados' => [], 'completados' => [], 'conflitos' => [], 'ids' => []];
        foreach ($cargos as $slug => $cargo) {
            if ($porSlug->has($slug)) {
                continue;
            }

            $mesmoNome = $porNome[$cargo['name']] ?? null;
            if ($mesmoNome === null) {
                $resolucao['criados'][] = $slug;
            } elseif ($mesmoNome->slug === null || $mesmoNome->slug === '') {
                $resolucao['completados'][] = $slug;
                if ($mesmoNome->deleted_at === null) {
                    $resolucao['ids'][$slug] = (int) $mesmoNome->id;
                }
                if (! $simular) {
                    $conexao->table(self::CARGOS)->where('id', $mesmoNome->id)->update(['slug' => $slug, 'updated_at' => now()]);
                }
            } else {
                $resolucao['conflitos'][$slug] = sprintf(
                    "nome '%s' ja usado pelo cargo id %s, slug '%s'",
                    $cargo['name'], $mesmoNome->id ?? '(novo)', $mesmoNome->slug,
                );
                continue;
            }

            // Reserva o nome: outro cargo do config com o mesmo nome vira conflito.
            $porNome[$cargo['name']] = (object) ['id' => $mesmoNome->id ?? null, 'slug' => $slug, 'deleted_at' => null];
        }

        if (! $simular) {
            $agora = now();
            $this->inserirEmLotes($conexao, self::CARGOS, array_map(static fn (string $slug): array => [
                ...$cargos[$slug],
                'guard_name' => $guard,
                'created_at' => $agora,
                'updated_at' => $agora,
            ], $resolucao['criados']));
        }

        return $resolucao;
    }

    /**
     * @param  list<string>  $criadas
     * @param  list<string>  $excluidas
     * @param  array{criados: list<string>, completados: list<string>, conflitos: array<string, string>, ids: array<string, int>}  $cargos
     * @return array{0: array<string, list<string>>, 1: array<string, list<string>>} [novas, pendentes], cargo => slugs
     */
    private function conceder(
        ConnectionInterface $conexao,
        string $guard,
        array $criadas,
        array $excluidas,
        array $cargos,
        bool $simular,
        bool $somenteNovas,
    ): array {
        $desejadas = array_diff_key($this->concessoesDesejadas($conexao, $guard, $criadas, $excluidas), $cargos['conflitos']);
        $idsCargos = $this->idsDosCargos($conexao, $guard, array_keys($desejadas)) + $cargos['ids'];
        $jaConcedidas = $this->concessoesExistentes($conexao, $guard, array_values($idsCargos));

        $novas = [];
        $pendentes = [];
        foreach ($desejadas as $cargo => $slugs) {
            $cargoNovo = in_array($cargo, $cargos['criados'], true);
            if (! isset($idsCargos[$cargo]) && ! ($simular && $cargoNovo)) {
                continue;
            }

            $faltantes = array_values(array_diff($slugs, $jaConcedidas[$idsCargos[$cargo] ?? 0] ?? []));
            $concedidas = $somenteNovas && ! $cargoNovo ? array_values(array_intersect($faltantes, $criadas)) : $faltantes;
            $adiadas = array_values(array_diff($faltantes, $concedidas));

            if ($concedidas !== []) {
                $novas[$cargo] = $concedidas;
            }
            if ($adiadas !== []) {
                $pendentes[$cargo] = $adiadas;
            }
        }

        if (! $simular && $novas !== []) {
            $this->gravarConcessoes($conexao, $guard, $novas, $idsCargos);
        }

        return [$novas, $pendentes];
    }

    /**
     * Pares do config com curinga expandido, mais o super-admin com toda
     * permissao nao excluida do guard, como no seeder.
     *
     * @param  list<string>  $criadas
     * @param  list<string>  $excluidas
     * @return array<string, list<string>>
     */
    private function concessoesDesejadas(ConnectionInterface $conexao, string $guard, array $criadas, array $excluidas): array
    {
        $desejadas = $this->catalogo->concessoesPorCargo();
        $todas = $conexao->table(self::PERMISSOES)
            ->where('guard_name', $guard)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('name')
            ->all();
        $desejadas[self::SUPER_ADMIN] = [...($desejadas[self::SUPER_ADMIN] ?? []), ...$todas, ...$criadas];

        return array_map(
            static fn (array $slugs): array => array_values(array_diff(array_unique($slugs), $excluidas)),
            $desejadas,
        );
    }

    /**
     * Com slug repetido vale o cargo de menor id, como o first() do seeder.
     *
     * @param  list<string>  $slugs
     * @return array<string, int>
     */
    private function idsDosCargos(ConnectionInterface $conexao, string $guard, array $slugs): array
    {
        return $conexao->table(self::CARGOS)
            ->where('guard_name', $guard)
            ->whereNull('deleted_at')
            ->whereIn('slug', $slugs)
            ->orderByDesc('id')
            ->pluck('id', 'slug')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  list<int>  $idsCargos
     * @return array<int, list<string>> id do cargo => slugs ja concedidos
     */
    private function concessoesExistentes(ConnectionInterface $conexao, string $guard, array $idsCargos): array
    {
        return $conexao->table(self::CONCESSOES.' as rp')
            ->join(self::PERMISSOES.' as p', 'p.id', '=', 'rp.permission_id')
            ->where('p.guard_name', $guard)
            ->whereIn('rp.role_id', $idsCargos)
            ->get(['rp.role_id', 'p.name'])
            ->groupBy('role_id')
            ->map(static fn (Collection $linhas): array => $linhas->pluck('name')->all())
            ->all();
    }

    /**
     * @param  array<string, list<string>>  $novas
     * @param  array<string, int>  $idsCargos
     */
    private function gravarConcessoes(ConnectionInterface $conexao, string $guard, array $novas, array $idsCargos): void
    {
        $idsPermissoes = $conexao->table(self::PERMISSOES)
            ->where('guard_name', $guard)
            ->whereNull('deleted_at')
            ->whereIn('name', array_values(array_unique(array_merge(...array_values($novas)))))
            ->pluck('id', 'name');

        $linhas = [];
        foreach ($novas as $cargo => $slugs) {
            foreach ($slugs as $slug) {
                if (isset($idsPermissoes[$slug], $idsCargos[$cargo])) {
                    $linhas[] = ['permission_id' => (int) $idsPermissoes[$slug], 'role_id' => $idsCargos[$cargo]];
                }
            }
        }

        $this->inserirEmLotes($conexao, self::CONCESSOES, $linhas);
    }

    /** @param  list<array<string, mixed>>  $linhas */
    private function inserirEmLotes(ConnectionInterface $conexao, string $tabela, array $linhas): void
    {
        foreach (array_chunk($linhas, self::LOTE_DE_INSERCAO) as $lote) {
            $conexao->table($tabela)->insertOrIgnore($lote);
        }
    }
}
