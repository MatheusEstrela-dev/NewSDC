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

        DB::beginTransaction();

        try {
            foreach ($mapping as $slug => $meta) {
                $ptbrName = $meta['name'] ?? ucfirst($slug);

                $correctRole = Role::where('slug', $slug)->first();

                $duplicates = Role::where('name', $slug)
                    ->where(function ($q) use ($slug) {
                        $q->where('slug', '!=', $slug)->orWhereNull('slug');
                    })
                    ->get();

                foreach ($duplicates as $dup) {
                    if ($dup->users()->count() === 0) {
                        $this->info("Removendo duplicado: {$dup->name} (id: {$dup->id})");
                        $dup->forceDelete();
                    } else {
                        $this->warn("Duplicado com usuarios, migrando: {$dup->name}");
                        if ($correctRole) {
                            DB::table('model_has_roles')
                                ->where('role_id', $dup->id)
                                ->update(['role_id' => $correctRole->id]);
                            $dup->forceDelete();
                        }
                    }
                }

                if ($correctRole && $correctRole->name !== $ptbrName) {
                    $correctRole->update(['name' => $ptbrName]);
                    $this->info("Atualizado: {$slug} -> {$ptbrName}");
                }
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
