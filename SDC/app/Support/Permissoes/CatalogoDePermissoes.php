<?php

declare(strict_types=1);

namespace App\Support\Permissoes;

use Illuminate\Contracts\Config\Repository;

/**
 * Leitura de config/permissions.php: o que o seeder e o sincronizador gravam.
 *
 * Fonte unica das regras de derivacao (descricao gerada, grupo e modulo em
 * minusculas, curinga `prefixo.*`, cargos a partir de `levels`), para o
 * seeder e o sincronizador nunca divergirem no que escrevem.
 */
final class CatalogoDePermissoes
{
    private const ROTULOS_DE_ACAO = [
        'view' => 'Visualizar',
        'create' => 'Criar',
        'edit' => 'Editar',
        'delete' => 'Deletar',
        'approve' => 'Aprovar',
        'assign' => 'Atribuir',
        'atribuir' => 'Atribuir',
        'finalize' => 'Finalizar',
        'manage' => 'Gerenciar',
        'execute' => 'Executar',
        'export' => 'Exportar',
        'send' => 'Enviar',
        'logs' => 'Visualizar Logs',
        'cache' => 'Limpar Cache',
        'settings' => 'Configuracoes',
        'print' => 'Imprimir',
        'pdf' => 'Gerar PDF',
        'history' => 'Visualizar Historico',
        'arquivar' => 'Arquivar',
        'validar' => 'Validar',
        'attachments' => 'Gerenciar Anexos',
        'desvincular' => 'Desvincular',
        'movimentar' => 'Movimentar',
        'encerrar_alheio' => 'Encerrar (Alheio)',
    ];

    public function __construct(
        private readonly Repository $config,
    ) {}

    public function guard(): string
    {
        return (string) $this->config->get('permissions.guard', 'web');
    }

    /**
     * Uma linha por slug; slug repetido no config fica com a ultima definicao,
     * como no updateOrCreate do seeder.
     *
     * @return array<string, array{slug: string, description: string, group: string, module: string, is_immutable: bool}>
     */
    public function permissoes(): array
    {
        $imutaveis = (array) $this->config->get('permissions.immutable_permissions', []);
        $permissoes = [];

        foreach ($this->percorrerModulos() as [$modulo, $grupo, $acao, $slug]) {
            $permissoes[$slug] = [
                'slug' => $slug,
                'description' => $this->descricao($modulo, $grupo, $acao),
                'group' => strtolower($grupo),
                'module' => strtolower($modulo),
                'is_immutable' => in_array($slug, $imutaveis, true),
            ];
        }

        return $permissoes;
    }

    /** @return list<string> todos os slugs dos modulos, na ordem do config */
    public function slugs(): array
    {
        return array_map(static fn (array $item): string => $item[3], $this->percorrerModulos());
    }

    /**
     * Cargos saem de `levels` (que da a hierarquia); `roles` so traz os metadados.
     *
     * @return array<string, array{slug: string, name: string, hierarchy_level: int, description: string, is_active: bool}>
     */
    public function cargos(): array
    {
        $metadados = (array) $this->config->get('permissions.roles', []);
        $cargos = [];

        foreach ((array) $this->config->get('permissions.levels', []) as $slug => $nivel) {
            $meta = $metadados[$slug] ?? [];
            $cargos[$slug] = [
                'slug' => $slug,
                'name' => $meta['name'] ?? ucfirst($slug),
                'hierarchy_level' => (int) $nivel,
                'description' => $meta['description'] ?? "Cargo {$slug}",
                'is_active' => (bool) ($meta['is_active'] ?? true),
            ];
        }

        return $cargos;
    }

    /** @return array<string, list<string>> cargo => slugs do config, com curinga expandido */
    public function concessoesPorCargo(): array
    {
        $todos = $this->slugs();
        $concessoes = [];

        foreach ((array) $this->config->get('permissions.role_permissions', []) as $cargo => $permissoes) {
            $concessoes[$cargo] = $this->expandirCuringas((array) $permissoes, $todos);
        }

        return $concessoes;
    }

    /**
     * `modulo.*` vira todo slug que comeca com `modulo.`; slug sem curinga so
     * entra se existir nos modulos.
     *
     * @param  list<string>  $permissoes
     * @param  list<string>  $todos
     * @return list<string>
     */
    public function expandirCuringas(array $permissoes, array $todos): array
    {
        $expandidas = [];

        foreach ($permissoes as $permissao) {
            if (str_ends_with($permissao, '.*')) {
                $prefixo = substr($permissao, 0, -1);
                foreach ($todos as $slug) {
                    if (str_starts_with($slug, $prefixo)) {
                        $expandidas[] = $slug;
                    }
                }
            } elseif (in_array($permissao, $todos, true)) {
                $expandidas[] = $permissao;
            }
        }

        return array_values(array_unique($expandidas));
    }

    public function descricao(string $modulo, string $grupo, string $acao): string
    {
        $rotulo = self::ROTULOS_DE_ACAO[$acao] ?? ucfirst($acao);

        return "{$rotulo} {$grupo} ({$modulo})";
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: string}> [modulo, grupo, acao, slug] */
    private function percorrerModulos(): array
    {
        $itens = [];

        foreach ((array) $this->config->get('permissions.modules', []) as $modulo => $grupos) {
            foreach ($grupos as $grupo => $acoes) {
                foreach ($acoes as $acao => $slug) {
                    $itens[] = [(string) $modulo, (string) $grupo, (string) $acao, (string) $slug];
                }
            }
        }

        return $itens;
    }
}
