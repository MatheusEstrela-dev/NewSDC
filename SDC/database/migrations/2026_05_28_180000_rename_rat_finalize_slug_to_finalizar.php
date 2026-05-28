<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Mapa de renomeacoes (slug antigo => slug novo).
     * Designed para suportar lotes futuros com escopo controlado.
     */
    private const RENAMES = [
        'rat.protocolos.finalize' => 'rat.protocolos.finalizar',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $oldSlug => $newSlug) {
            $this->renameOnPermissions($oldSlug, $newSlug);
            $this->renameOnTokens($oldSlug, $newSlug);
        }

        $this->forgetSpatieCache();
    }

    public function down(): void
    {
        foreach (self::RENAMES as $oldSlug => $newSlug) {
            $this->renameOnPermissions($newSlug, $oldSlug);
            $this->renameOnTokens($newSlug, $oldSlug);
        }

        $this->forgetSpatieCache();
    }

    /**
     * Renomeia in-place na tabela permissions.
     * Preserva FKs em role_has_permissions e model_has_permissions (permission_id intacto).
     */
    private function renameOnPermissions(string $from, string $to): void
    {
        DB::table('permissions')
            ->where('name', $from)
            ->whereNull('deleted_at')
            ->update([
                'name' => $to,
                'slug' => $to,
                'updated_at' => now(),
            ]);
    }

    /**
     * Atualiza abilities JSON de tokens Sanctum vivos que contenham o slug antigo.
     * Tokens Sanctum congelam abilities no momento da criacao (string textual),
     * por isso precisamos atualizar para preservar autorizacao das integracoes.
     */
    private function renameOnTokens(string $from, string $to): void
    {
        if (!DB::getSchemaBuilder()->hasTable('personal_access_tokens')) {
            return;
        }

        $tokens = DB::table('personal_access_tokens')
            ->where('abilities', 'like', '%' . $from . '%')
            ->get(['id', 'abilities']);

        foreach ($tokens as $token) {
            $updated = str_replace($from, $to, $token->abilities);

            if ($updated !== $token->abilities) {
                DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->update(['abilities' => $updated]);
            }
        }
    }

    /**
     * Limpa o cache de permissions do Spatie para refletir as renomeacoes imediatamente.
     */
    private function forgetSpatieCache(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
