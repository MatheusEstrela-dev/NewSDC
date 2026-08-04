<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Torna `inscricoes` polimorfica (inscrito_type/inscrito_id) para aceitar tanto
 * servidores internos (App\Models\User, guard "web") quanto cidadaos externos
 * (App\Modules\Treinamento\Models\Cidadao, guard "cidadao") no mesmo fluxo de
 * inscricao/aprovacao. Seguro fazer agora: nenhum Controller ainda escreve em
 * `inscricoes`/`frequencias` em producao (modulo Treinamento so tinha
 * index/show/store de Treinamento ate aqui).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->string('inscrito_type')->nullable()->after('treinamento_id');
            $table->unsignedBigInteger('inscrito_id')->nullable()->after('inscrito_type');
            $table->uuid('qr_code_token')->nullable()->after('status');
        });

        DB::table('inscricoes')->orderBy('id')->select('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('inscricoes')->where('id', $row->id)->update([
                    'inscrito_type' => User::class,
                    'inscrito_id' => DB::raw('user_id'),
                    'qr_code_token' => (string) Str::uuid(),
                ]);
            }
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropUnique('idx_treinamento_user_unique');
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->string('inscrito_type')->nullable(false)->change();
            $table->unsignedBigInteger('inscrito_id')->nullable(false)->change();
            $table->uuid('qr_code_token')->nullable(false)->change();

            $table->unique(['treinamento_id', 'inscrito_type', 'inscrito_id'], 'idx_treinamento_inscrito_unique');
            $table->unique('qr_code_token');
            $table->index(['inscrito_type', 'inscrito_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('treinamento_id')->constrained('users')->cascadeOnDelete();
        });

        DB::table('inscricoes')->orderBy('id')->select('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('inscricoes')->where('id', $row->id)->update([
                    'user_id' => DB::raw('inscrito_id'),
                ]);
            }
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropUnique('idx_treinamento_inscrito_unique');
            $table->dropUnique(['qr_code_token']);
            $table->dropIndex(['inscrito_type', 'inscrito_id']);
            $table->dropColumn(['inscrito_type', 'inscrito_id', 'qr_code_token']);
            $table->unique(['treinamento_id', 'user_id'], 'idx_treinamento_user_unique');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
