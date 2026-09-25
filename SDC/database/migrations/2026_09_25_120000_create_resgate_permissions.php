<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissoes do resgate de pontos (plano 2026-09-25). Migration principal
 * das permissoes do modulo: cada fase acrescenta as suas aqui.
 *
 * Criadas aqui, e nao so no seeder, para o deploy nao depender de reseed.
 *
 * - resgate.carteira.view   : ver a carteira do proprio municipio/orgao.
 * - resgate.carteira.estado : ver a carteira de qualquer ente (visao estadual).
 * - resgate.solicitar       : operar resgate EM NOME do ente (decisao D1).
 * - resgate.catalogo.ver    : ver o catalogo de premios.
 * - resgate.catalogo.propor : propor item, nova versao ou encerramento, e
 *                             cadastrar unidades de bem.
 * - resgate.catalogo.aprovar: aprovar ou recusar proposta de OUTRA pessoa.
 *
 * `solicitar`, `propor` e `aprovar` NAO vao para role nenhuma: sao
 * permissoes especiais, concedidas pessoa a pessoa no Permissionamento, onde
 * cada concessao fica no permission_audit_log com IP, user agent e sessao.
 * A carteira segue quem ja ve o placar estadual; o catalogo, quem ve o placar.
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
        'resgate.catalogo.ver'    => ['Resgate - Catalogo - Ver', 'catalogo'],
        'resgate.catalogo.propor' => ['Resgate - Catalogo - Propor item ou versao', 'catalogo'],
        'resgate.catalogo.aprovar' => ['Resgate - Catalogo - Aprovar proposta de outra pessoa', 'catalogo'],
    ];

    /** Permissao concedida => roles que ja tem a de referencia. */
    private const HERANCA = [
        'resgate.carteira.view'   => 'ranking.placar.estado',
        'resgate.carteira.estado' => 'ranking.placar.estado',
        'resgate.catalogo.ver'    => 'ranking.placar.view',
    ];

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

        foreach (self::HERANCA as $slug => $referencia) {
            $permissaoId = DB::table('permissions')->where('name', $slug)->where('guard_name', 'web')->value('id');
            $roles = DB::table('role_has_permissions as rp')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('p.name', $referencia)
                ->where('p.guard_name', 'web')
                ->pluck('rp.role_id');

            foreach ($roles as $roleId) {
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
