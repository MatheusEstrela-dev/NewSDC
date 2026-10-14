<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Termo de interdicao do RAT (#57).
 *
 * Um termo por ocorrencia. O RAT original (-001) e onde o COMPDEC registra a
 * interdicao; o relacionado (-002, -003...) parte do termo da origem
 * (`origem_termo_id`) para o agente com registro no CREA emitir o seu.
 *
 * As duas permissoes nascem aqui, e nao so no seeder, para o deploy nao
 * depender de reseed (mesmo padrao de tdap.viagens.reprovar).
 */
return new class extends Migration
{
    private const PERMISSOES = [
        'rat.vistoria.interditar' => ['descricao' => 'Rat - Vistoria - Termo de interdicao', 'roles' => ['admin', 'manager', 'compdec']],
        'rat.vistoria.crea'       => ['descricao' => 'Rat - Vistoria - Emitir termo como agente CREA', 'roles' => ['admin']],
    ];

    public function up(): void
    {
        Schema::create('rat_termos_interdicao', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ocorrencia_id')->unique();
            $table->uuid('origem_termo_id')->nullable();
            $table->string('tipo', 10);
            $table->text('area_interditada')->nullable();
            $table->text('fundamentacao')->nullable();
            $table->text('medidas')->nullable();
            $table->unsignedSmallInteger('prazo_dias')->nullable();
            $table->string('responsavel_nome', 191)->nullable();
            $table->string('registro_crea', 30)->nullable();
            $table->string('status', 10)->default('rascunho');
            $table->unsignedBigInteger('emitido_por')->nullable();
            $table->timestamp('emitido_em')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('ocorrencia_id')->references('id')->on('rat_ocorrencias')->cascadeOnDelete();
            $table->foreign('emitido_por')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('rat_termos_interdicao', function (Blueprint $table) {
            $table->foreign('origem_termo_id')->references('id')->on('rat_termos_interdicao')->nullOnDelete();
        });

        DB::statement("ALTER TABLE rat_termos_interdicao ADD CONSTRAINT rat_termos_interdicao_tipo_check CHECK (tipo IN ('parcial','total'))");
        DB::statement("ALTER TABLE rat_termos_interdicao ADD CONSTRAINT rat_termos_interdicao_status_check CHECK (status IN ('rascunho','emitido'))");

        $agora = now();
        foreach (self::PERMISSOES as $slug => $cfg) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $slug, 'guard_name' => 'web'],
                [
                    'slug'         => $slug,
                    'description'  => $cfg['descricao'],
                    'group'        => 'vistoria',
                    'module'       => 'rat',
                    'is_active'    => true,
                    'is_immutable' => false,
                    'deleted_at'   => null,
                    'created_at'   => $agora,
                    'updated_at'   => $agora,
                ],
            );

            $permissaoId = DB::table('permissions')->where('name', $slug)->where('guard_name', 'web')->value('id');
            $roles = DB::table('roles')->whereIn('slug', $cfg['roles'])->pluck('id');
            foreach ($roles as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissaoId, 'role_id' => $roleId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_termos_interdicao');

        $ids = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSOES))->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
