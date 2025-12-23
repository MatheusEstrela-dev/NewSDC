<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $permissionsTable = config('permission.table_names.permissions', 'permissions');

        Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            if (!Schema::hasColumn($rolesTable, 'slug')) {
                $table->string('slug')->nullable()->after('name');
                $table->index('slug');
            }

            if (!Schema::hasColumn($rolesTable, 'hierarchy_level')) {
                $table->integer('hierarchy_level')->default(99)->after('slug');
                $table->index('hierarchy_level');
            }

            if (!Schema::hasColumn($rolesTable, 'description')) {
                $table->text('description')->nullable()->after('hierarchy_level');
            }

            if (!Schema::hasColumn($rolesTable, 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
                $table->index('is_active');
            }
        });

        Schema::table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
            if (!Schema::hasColumn($permissionsTable, 'slug')) {
                $table->string('slug')->nullable()->after('name');
                $table->index('slug');
            }

            if (!Schema::hasColumn($permissionsTable, 'description')) {
                $table->text('description')->nullable()->after('slug');
            }

            if (!Schema::hasColumn($permissionsTable, 'group')) {
                $table->string('group')->default('general')->after('description');
                $table->index('group');
            }

            if (!Schema::hasColumn($permissionsTable, 'module')) {
                $table->string('module', 50)->nullable()->after('group');
                $table->index('module');
            }

            if (!Schema::hasColumn($permissionsTable, 'is_active')) {
                $table->boolean('is_active')->default(true)->after('module');
                $table->index('is_active');
            }

            if (!Schema::hasColumn($permissionsTable, 'is_immutable')) {
                $table->boolean('is_immutable')->default(false)->after('is_active');
                $table->index('is_immutable');
            }
        });
    }

    public function down(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $permissionsTable = config('permission.table_names.permissions', 'permissions');

        Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            if (Schema::hasColumn($rolesTable, 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn($rolesTable, 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn($rolesTable, 'hierarchy_level')) {
                $table->dropColumn('hierarchy_level');
            }
            if (Schema::hasColumn($rolesTable, 'slug')) {
                $table->dropColumn('slug');
            }
        });

        Schema::table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
            if (Schema::hasColumn($permissionsTable, 'is_immutable')) {
                $table->dropColumn('is_immutable');
            }
            if (Schema::hasColumn($permissionsTable, 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn($permissionsTable, 'module')) {
                $table->dropColumn('module');
            }
            if (Schema::hasColumn($permissionsTable, 'group')) {
                $table->dropColumn('group');
            }
            if (Schema::hasColumn($permissionsTable, 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn($permissionsTable, 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};


