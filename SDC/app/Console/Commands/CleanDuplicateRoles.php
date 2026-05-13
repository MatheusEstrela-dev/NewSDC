<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateRoles extends Command
{
    protected $signature = 'roles:clean-duplicates';
    protected $description = 'Remove roles duplicados com nomes em ingles';

    public function handle(): int
    {
        $mapping = config('permissions.roles', []);
        $guard = config('auth.defaults.guard', 'web');

        DB::beginTransaction();

        try {
            foreach ($mapping as $slug => $meta) {
                $ptbrName = $meta['name'] ?? ucfirst($slug);

                $correctRole = Role::where('slug', $slug)
                    ->where('guard_name', $guard)
                    ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$ptbrName])
                    ->orderBy('id')
                    ->first();

                if (!$correctRole) {
                    $this->warn("Cargo canonico nao encontrado para slug: {$slug}");
                    continue;
                }

                $duplicates = Role::where('guard_name', $guard)
                    ->where('id', '!=', $correctRole->id)
                    ->where(function ($q) use ($slug) {
                        $q->where('slug', $slug)
                            ->orWhere('name', $slug);
                    })
                    ->get();

                foreach ($duplicates as $dup) {
                    $this->warn("Migrando duplicado: {$dup->name} (id: {$dup->id}) -> {$correctRole->name} (id: {$correctRole->id})");

                    DB::table('model_has_roles')
                        ->where('role_id', $dup->id)
                        ->update(['role_id' => $correctRole->id]);

                    $permissionIds = DB::table('role_has_permissions')
                        ->where('role_id', $dup->id)
                        ->pluck('permission_id');

                    foreach ($permissionIds as $permissionId) {
                        DB::table('role_has_permissions')->updateOrInsert([
                            'role_id' => $correctRole->id,
                            'permission_id' => $permissionId,
                        ]);
                    }

                    DB::table('role_has_permissions')->where('role_id', $dup->id)->delete();
                    $dup->forceDelete();
                }

                if ($correctRole && $correctRole->name !== $ptbrName) {
                    $correctRole->update([
                        'name' => $ptbrName,
                        'slug' => $slug,
                        'hierarchy_level' => config("permissions.levels.{$slug}", $correctRole->hierarchy_level),
                    ]);
                    $this->info("Atualizado: {$slug} -> {$ptbrName}");
                }
            }

            $orphans = Role::where('guard_name', $guard)
                ->where(function ($q) {
                    $q->whereNull('slug')->orWhere('slug', '');
                })
                ->get();

            foreach ($orphans as $orphan) {
                if ($orphan->users()->count() > 0 || $orphan->permissions()->count() > 0) {
                    $this->warn("Cargo sem slug mantido por possuir vinculos: {$orphan->name} (id: {$orphan->id})");
                    continue;
                }

                $this->info("Removendo cargo orfao sem slug: {$orphan->name} (id: {$orphan->id})");
                $orphan->forceDelete();
            }

            DB::commit();
            $this->info('Limpeza concluida com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erro: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
