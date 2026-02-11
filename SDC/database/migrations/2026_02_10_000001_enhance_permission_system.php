<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration consolidada para melhorias no sistema de permissionamento.
 *
 * Inclui:
 * - Soft deletes em roles e permissions
 * - TTL (expires_at) para permissoes temporarias
 * - Campos extras no audit log (reason, session_id)
 * - Indices compostos para performance
 * - CHECK constraints para integridade
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Soft Deletes
        if (!Schema::hasColumn('roles', 'deleted_at')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('permissions', 'deleted_at')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 2. TTL para permissoes temporarias
        if (!Schema::hasColumn('model_has_roles', 'expires_at')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('model_id');
                $table->index('expires_at', 'model_has_roles_expires_at_idx');
            });
        }

        if (!Schema::hasColumn('model_has_permissions', 'expires_at')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('model_id');
                $table->index('expires_at', 'model_has_permissions_expires_at_idx');
            });
        }

        // 3. Campos extras no audit log
        if (Schema::hasTable('permission_audit_log')) {
            if (!Schema::hasColumn('permission_audit_log', 'reason')) {
                Schema::table('permission_audit_log', function (Blueprint $table) {
                    $table->string('reason', 500)->nullable()->after('after_state');
                });
            }

            if (!Schema::hasColumn('permission_audit_log', 'session_id')) {
                Schema::table('permission_audit_log', function (Blueprint $table) {
                    $table->string('session_id', 100)->nullable()->after('user_agent');
                });
            }
        }

        // 4. Indices compostos para performance
        $this->addIndexIfNotExists('roles', ['guard_name', 'hierarchy_level', 'is_active'], 'roles_guard_hierarchy_active_idx');
        $this->addIndexIfNotExists('permissions', ['guard_name', 'group', 'is_active'], 'permissions_guard_group_active_idx');
        $this->addIndexIfNotExists('permissions', ['module', 'is_active'], 'permissions_module_active_idx');
        $this->addIndexIfNotExists('model_has_roles', ['model_type', 'role_id'], 'model_has_roles_type_role_idx');

        // 5. CHECK constraints (MySQL 8+)
        $this->addCheckConstraintIfNotExists('roles', 'chk_hierarchy_level', 'hierarchy_level >= 0 AND hierarchy_level <= 99');
        $this->addCheckConstraintIfNotExists('roles', 'chk_roles_is_active', 'is_active IN (0, 1)');
        $this->addCheckConstraintIfNotExists('permissions', 'chk_permissions_is_active', 'is_active IN (0, 1)');
        $this->addCheckConstraintIfNotExists('permissions', 'chk_is_immutable', 'is_immutable IN (0, 1)');
    }

    public function down(): void
    {
        // Remove CHECK constraints
        $this->dropCheckConstraintIfExists('permissions', 'chk_is_immutable');
        $this->dropCheckConstraintIfExists('permissions', 'chk_permissions_is_active');
        $this->dropCheckConstraintIfExists('roles', 'chk_roles_is_active');
        $this->dropCheckConstraintIfExists('roles', 'chk_hierarchy_level');

        // Remove indices compostos
        $this->dropIndexIfExists('model_has_roles', 'model_has_roles_type_role_idx');
        $this->dropIndexIfExists('permissions', 'permissions_module_active_idx');
        $this->dropIndexIfExists('permissions', 'permissions_guard_group_active_idx');
        $this->dropIndexIfExists('roles', 'roles_guard_hierarchy_active_idx');

        // Remove campos do audit log
        if (Schema::hasTable('permission_audit_log')) {
            Schema::table('permission_audit_log', function (Blueprint $table) {
                if (Schema::hasColumn('permission_audit_log', 'session_id')) {
                    $table->dropColumn('session_id');
                }
                if (Schema::hasColumn('permission_audit_log', 'reason')) {
                    $table->dropColumn('reason');
                }
            });
        }

        // Remove TTL
        if (Schema::hasColumn('model_has_permissions', 'expires_at')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->dropIndex('model_has_permissions_expires_at_idx');
                $table->dropColumn('expires_at');
            });
        }

        if (Schema::hasColumn('model_has_roles', 'expires_at')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropIndex('model_has_roles_expires_at_idx');
                $table->dropColumn('expires_at');
            });
        }

        // Remove soft deletes
        if (Schema::hasColumn('permissions', 'deleted_at')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('roles', 'deleted_at')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    /**
     * Adiciona indice se nao existir (compativel com MySQL e SQLite).
     */
    protected function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list({$table})"))->pluck('name');
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->unique();
        }

        if (!$indexes->contains($indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->index($columns, $indexName);
            });
        }
    }

    /**
     * Remove indice se existir (compativel com MySQL e SQLite).
     */
    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect(DB::select("PRAGMA index_list({$table})"))->pluck('name');
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->unique();
        }

        if ($indexes->contains($indexName)) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        }
    }

    /**
     * Adiciona CHECK constraint se nao existir (apenas MySQL 8+).
     */
    protected function addCheckConstraintIfNotExists(string $table, string $constraintName, string $expression): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND CONSTRAINT_TYPE = 'CHECK'
        ");

        $constraintNames = collect($constraints)->pluck('CONSTRAINT_NAME');

        if (!$constraintNames->contains($constraintName)) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} CHECK ({$expression})");
        }
    }

    /**
     * Remove CHECK constraint se existir (apenas MySQL).
     */
    protected function dropCheckConstraintIfExists(string $table, string $constraintName): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND CONSTRAINT_TYPE = 'CHECK'
        ");

        $constraintNames = collect($constraints)->pluck('CONSTRAINT_NAME');

        if ($constraintNames->contains($constraintName)) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraintName}");
        }
    }
};
