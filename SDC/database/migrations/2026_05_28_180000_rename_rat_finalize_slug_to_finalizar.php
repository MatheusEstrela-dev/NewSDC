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
        DB::transaction(function () {
            foreach (self::RENAMES as $oldSlug => $newSlug) {
                $this->renameOnPermissions($oldSlug, $newSlug);
                $this->renameOnTokens($oldSlug, $newSlug);
            }
        });

        $this->forgetSpatieCache();
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (self::RENAMES as $oldSlug => $newSlug) {
                $this->renameOnPermissions($newSlug, $oldSlug);
                $this->renameOnTokens($newSlug, $oldSlug);
            }
        });

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
     * Tokens Sanctum congelam abilities no momento da criacao (JSON array textual),
     * por isso precisamos atualizar para preservar autorizacao das integracoes.
     *
     * Faz match exato por elemento do array (json_decode -> compare -> json_encode)
     * para evitar falso-positivo de substring caso surja outro slug que CONTENHA
     * o slug antigo como prefixo (ex: 'rat.protocolos.finalize' vs 'rat.protocolos.finalize_x').
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
            $abilities = json_decode((string) $token->abilities, true);
            if (!is_array($abilities)) {
                continue;
            }

            $changed = false;
            foreach ($abilities as $i => $ability) {
                if ($ability === $from) {
                    $abilities[$i] = $to;
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->update(['abilities' => json_encode(array_values($abilities))]);
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
