<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class AuditPermissionsCommand extends Command
{
    protected $signature = 'permissions:audit
        {--strict : Exit code 1 se houver qualquer divergencia}
        {--no-backend : Pula varredura de uso em controllers/policies/middlewares}';

    protected $description = 'Audita sincronia entre config/permissions.php, tabela permissions e usos no backend (controllers, policies, rotas)';

    private array $configSlugs = [];
    private array $dbSlugs = [];
    private array $backendUsages = [];

    public function handle(): int
    {
        $this->components->info('Auditoria de Permissoes - sincronia das camadas');
        $this->newLine();

        $this->configSlugs = $this->extractConfigSlugs();
        $this->dbSlugs = $this->extractDbSlugs();
        $this->backendUsages = $this->option('no-backend') ? [] : $this->extractBackendUsages();

        $this->renderSummary();
        $this->newLine();

        $orphans = $this->reportOrphansInDatabase();
        $missingInDb = $this->reportMissingInDatabase();
        $undefinedUsages = $this->option('no-backend') ? [] : $this->reportUndefinedUsages();

        $hasIssues = !empty($orphans) || !empty($missingInDb) || !empty($undefinedUsages);

        $this->newLine();

        if ($hasIssues) {
            if ($this->option('strict')) {
                $this->components->error('Auditoria falhou (--strict). Resolva os problemas acima.');
                return Command::FAILURE;
            }
            $this->components->warn('Auditoria concluida COM avisos.');
        } else {
            $this->components->info('Auditoria concluida SEM problemas.');
        }

        return Command::SUCCESS;
    }

    private function renderSummary(): void
    {
        $this->components->twoColumnDetail('Slugs declarados em config/permissions.php', (string) count($this->configSlugs));
        $this->components->twoColumnDetail('Permissoes ativas no banco', (string) count($this->dbSlugs));
        if (!$this->option('no-backend')) {
            $this->components->twoColumnDetail('Usos detectados em controllers/policies/rotas', (string) count($this->backendUsages));
        }
    }

    private function reportOrphansInDatabase(): array
    {
        $orphans = array_values(array_diff($this->dbSlugs, $this->configSlugs));

        if (empty($orphans)) {
            $this->components->task('Slugs orfaos no banco', fn () => true);
            return [];
        }

        $this->components->task('Slugs orfaos no banco', fn () => false);
        $this->components->bulletList(array_map(
            fn ($slug) => "<fg=yellow>{$slug}</> (existe no banco mas nao no config)",
            $orphans
        ));

        return $orphans;
    }

    private function reportMissingInDatabase(): array
    {
        $missing = array_values(array_diff($this->configSlugs, $this->dbSlugs));

        if (empty($missing)) {
            $this->components->task('Slugs do config no banco', fn () => true);
            return [];
        }

        $this->components->task('Slugs do config no banco', fn () => false);
        $this->components->bulletList(array_map(
            fn ($slug) => "<fg=red>{$slug}</> (no config, ausente no banco — rodar db:seed)",
            $missing
        ));

        return $missing;
    }

    private function reportUndefinedUsages(): array
    {
        $undefined = array_filter(
            $this->backendUsages,
            fn ($usage) => !in_array($usage['slug'], $this->configSlugs, true)
        );

        if (empty($undefined)) {
            $this->components->task('Usos backend cobertos pelo config', fn () => true);
            return [];
        }

        $this->components->task('Usos backend cobertos pelo config', fn () => false);

        $grouped = [];
        foreach ($undefined as $u) {
            $grouped[$u['slug']][] = "{$u['file']}:{$u['line']}";
        }

        foreach ($grouped as $slug => $locations) {
            $this->line("  <fg=red>{$slug}</> em:");
            foreach ($locations as $loc) {
                $this->line("    - {$loc}");
            }
        }

        return array_values($undefined);
    }

    private function extractConfigSlugs(): array
    {
        $modules = config('permissions.modules', []);
        $slugs = [];

        foreach ($modules as $groups) {
            foreach ($groups as $actions) {
                foreach ($actions as $slug) {
                    if (is_string($slug)) {
                        $slugs[] = $slug;
                    }
                }
            }
        }

        return array_values(array_unique($slugs));
    }

    private function extractDbSlugs(): array
    {
        return DB::table('permissions')
            ->whereNull('deleted_at')
            ->pluck('name')
            ->toArray();
    }

    private function extractBackendUsages(): array
    {
        $usages = [];
        $roots = [app_path(), base_path('routes')];

        $patterns = [
            // can:slug ou ability:slug em middlewares
            "/->middleware\(\s*['\"](?:can|permission|role_or_permission|ability):([a-z][a-z0-9_.-]+\.[a-z0-9_.-]+)['\"]\s*\)/",
            // \$user->can('slug') / Gate::allows('slug') / can('slug')
            "/(?:->can|Gate::(?:allows|denies|authorize)|hasPermissionTo)\(\s*['\"]([a-z][a-z0-9_.-]+\.[a-z0-9_.-]+)['\"]/",
            // checkPermissionOrDeny($user, 'slug')
            "/checkPermissionOrDeny\([^,]+,\s*['\"]([a-z][a-z0-9_.-]+\.[a-z0-9_.-]+)['\"]/",
        ];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            $files = File::allFiles($root);

            foreach ($files as $file) {
                if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                    continue;
                }

                $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES);
                if ($lines === false) {
                    continue;
                }

                $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $relative = str_replace('\\', '/', $relative);

                foreach ($lines as $i => $line) {
                    foreach ($patterns as $pattern) {
                        if (preg_match_all($pattern, $line, $matches)) {
                            foreach ($matches[1] as $slug) {
                                $usages[] = [
                                    'slug' => $slug,
                                    'file' => $relative,
                                    'line' => $i + 1,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $usages;
    }
}
