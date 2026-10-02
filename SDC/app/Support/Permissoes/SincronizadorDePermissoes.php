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
 * concede o par cargo -> permissao que falta. Nunca revoga, nunca apaga, nunca
 * sobrescreve valor preenchido a mao (descricao, is_active, nome do cargo) e
 * nao restaura permissao excluida a mao. Idempotente e seguro com processos
 * concorrentes (insertOrIgnore sobre as chaves unicas).
 *
 * Query builder, e nao os models: os eventos deles limpam o cache do Spatie a
 * cada save; aqui o cache e limpo uma vez, no fim.
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

    public function sincronizar(bool $simular = false): RelatorioDeSincronizacao
    {
        $conexao = $this->db->connection();

        if ($simular) {
            return $this->executar($conexao, true);
        }

        $relatorio = $conexao->transaction(fn (): RelatorioDeSincronizacao => $this->executar($conexao, false));
        $this->registrar->forgetCachedPermissions();

        return $relatorio;
    }

    private function executar(ConnectionInterface $conexao, bool $simular): RelatorioDeSincronizacao
    {
        $guard = $this->catalogo->guard();
        $definidas = $this->catalogo->permissoes();
        $existentes = $conexao->table(self::PERMISSOES)
            ->where('guard_name', $guard)
            ->whereIn('name', array_keys($definidas))
            ->get(['id', 'name', 'deleted_at', 'created_at', ...self::COLUNAS_COMPLETAVEIS])
            ->keyBy('name');

        $criadas = array_values(array_diff(array_keys($definidas), $existentes->keys()->all()));
        $excluidas = $existentes->whereNotNull('deleted_at')->keys()->values()->all();
        $completadas = $this->completarPermissoes($conexao, $existentes->whereNull('deleted_at'), $definidas, $simular);
        $cargosCriados = $this->criarCargos($conexao, $guard, $simular);

        if (! $simular) {
            $this->criarPermissoes($conexao, $guard, array_intersect_key($definidas, array_flip($criadas)));
        }

        $concessoes = $this->concederFaltantes($conexao, $guard, $criadas, $excluidas, $cargosCriados, $simular);

        return new RelatorioDeSincronizacao($simular, $criadas, $completadas, $excluidas, $cargosCriados, $concessoes);
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
     * @param  Collection<string, object>  $existentes
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
     * Cargo ausente e o que nao tem linha com o slug, nem excluida: cargo
     * excluido a mao continua excluido.
     *
     * @return list<string>
     */
    private function criarCargos(ConnectionInterface $conexao, string $guard, bool $simular): array
    {
        $cargos = $this->catalogo->cargos();
        $existentes = $conexao->table(self::CARGOS)
            ->where('guard_name', $guard)
            ->whereIn('slug', array_keys($cargos))
            ->pluck('slug')
            ->all();
        $ausentes = array_diff_key($cargos, array_flip($existentes));

        if (! $simular) {
            $agora = now();
            $this->inserirEmLotes($conexao, self::CARGOS, array_map(static fn (array $cargo): array => [
                ...$cargo,
                'guard_name' => $guard,
                'created_at' => $agora,
                'updated_at' => $agora,
            ], array_values($ausentes)));
        }

        return array_keys($ausentes);
    }

    /**
     * @param  list<string>  $criadas
     * @param  list<string>  $excluidas
     * @param  list<string>  $cargosCriados
     * @return array<string, list<string>> cargo => slugs concedidos agora
     */
    private function concederFaltantes(
        ConnectionInterface $conexao,
        string $guard,
        array $criadas,
        array $excluidas,
        array $cargosCriados,
        bool $simular,
    ): array {
        $desejadas = $this->concessoesDesejadas($conexao, $guard, $criadas, $excluidas);
        $idsCargos = $this->idsDosCargos($conexao, $guard, array_keys($desejadas));
        $jaConcedidas = $this->concessoesExistentes($conexao, $guard, array_values($idsCargos));

        $novas = [];
        foreach ($desejadas as $cargo => $slugs) {
            $existe = isset($idsCargos[$cargo]) || ($simular && in_array($cargo, $cargosCriados, true));
            if (! $existe) {
                continue;
            }

            $faltantes = array_values(array_diff($slugs, $jaConcedidas[$idsCargos[$cargo] ?? 0] ?? []));
            if ($faltantes !== []) {
                $novas[$cargo] = $faltantes;
            }
        }

        if (! $simular && $novas !== []) {
            $this->gravarConcessoes($conexao, $guard, $novas, $idsCargos);
        }

        return $novas;
    }

    /**
     * Pares do config com curinga expandido, mais o super-admin com toda
     * permissao ativa do guard, como no seeder.
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
                if (isset($idsPermissoes[$slug])) {
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
