<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissoes do resgate de pontos (plano 2026-09-25, Fase 1).
 *
 * Criadas aqui, e nao so no seeder, para o deploy nao depender de reseed.
 *
 * - resgate.carteira.view   : ver a carteira do proprio municipio/orgao.
 * - resgate.carteira.estado : ver a carteira de qualquer ente (visao estadual).
 * - resgate.solicitar       : operar resgate EM NOME do ente (decisao D1).
 *
 * `resgate.solicitar` NAO vai para role nenhuma: e permissao especial,
 * concedida pessoa a pessoa no Permissionamento, onde cada concessao fica no
 * permission_audit_log com IP, user agent e sessao. A visao da carteira segue
 * quem ja ve o placar estadual.
 *
 * Query builder, e nao os models: os eventos deles limpam o cache a cada
 * save; a migration limpa uma vez, no fim.
 */
return new class extends Migration
{
    private const PERMISSOES = [
        'resgate.carteira.view'   => ['Resgate - Carteira - Ver', 'carteira'],
        'resgate.carteira.estado' => ['Resgate - Carteira - Visao estadual', 'carteira'],
        'resgate.solicitar'       => ['Resgate - Pedidos - Solicitar em nome do ente', 'pedidos'],
    ];

    private const VISAO_PARA_QUEM_TEM = 'ranking.placar.estado';

    public function up(): void
    {
        $agora = now();

        foreach (self::PERMISSOES as $slug => [$descricao, $grupo]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $slug, 'guard_name' => 'web'],
                [
                    'slug'         => $slug,
                    'description'  => $descricao,
                    'group'        => $grupo,
                    'module'       => 'resgate',
                    'is_active'    => true,
                    'is_immutable' => false,
                    'deleted_at'   => null,
                    'created_at'   => $agora,
                    'updated_at'   => $agora,
                ],
            );
        }

        $roles = DB::table('role_has_permissions as rp')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('p.name', self::VISAO_PARA_QUEM_TEM)
            ->where('p.guard_name', 'web')
            ->pluck('rp.role_id');

        $visao = DB::table('permissions')
            ->whereIn('name', ['resgate.carteira.view', 'resgate.carteira.estado'])
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($roles as $roleId) {
            foreach ($visao as $permissaoId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissaoId,
                    'role_id'       => $roleId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSOES))
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
