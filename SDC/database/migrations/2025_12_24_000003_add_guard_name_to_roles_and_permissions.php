<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compatibilidade: bancos antigos podem ter tabelas roles/permissions sem guard_name
        // (necessário para Spatie Permission funcionar corretamente).

        if (Schema::hasTable('permissions') && !Schema::hasColumn('permissions', 'guard_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
                $table->index('guard_name');
            });

            // Popular registros existentes
            DB::table('permissions')->whereNull('guard_name')->update(['guard_name' => 'web']);

            // Ajustar unique: Spatie usa (name, guard_name)
            try {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->dropUnique('permissions_name_unique');
                });
            } catch (\Throwable $e) {
                // ignora se não existir
            }
            Schema::table('permissions', function (Blueprint $table) {
                $table->unique(['name', 'guard_name']);
            });
        }

        if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'guard_name')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('name');
                $table->index('guard_name');
            });

            DB::table('roles')->whereNull('guard_name')->update(['guard_name' => 'web']);

            try {
                Schema::table('roles', function (Blueprint $table) {
                    $table->dropUnique('roles_name_unique');
                });
            } catch (\Throwable $e) {
                // ignora se não existir
            }
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['name', 'guard_name']);
            });
        }
    }

    public function down(): void
    {
        // Não removemos guard_name para evitar quebrar o Spatie Permission em ambientes existentes.
    }
};


