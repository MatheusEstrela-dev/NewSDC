<?php

declare(strict_types=1);

use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Support\EstimativaNotificacaoFeam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PAE Subprojeto A (Resolucao GMG 83/2024): prazos legais, CCPAE e dilacao.
 * Migration consolidada do subprojeto: toda mudanca de schema do A fica aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pae_protocolos', function (Blueprint $table) {
            if (! Schema::hasColumn('pae_protocolos', 'dt_notificacao_feam')) {
                $table->date('dt_notificacao_feam')->nullable();
            }
            if (! Schema::hasColumn('pae_protocolos', 'dt_notificacao_feam_estimada')) {
                $table->boolean('dt_notificacao_feam_estimada')->default(false);
            }
            if (! Schema::hasColumn('pae_protocolos', 'ciclos_esgotados_em')) {
                $table->date('ciclos_esgotados_em')->nullable()->index();
            }
        });

        Schema::table('pae_dilacoes', function (Blueprint $table) {
            if (! Schema::hasColumn('pae_dilacoes', 'pae_notificacao_id')) {
                $table->foreignId('pae_notificacao_id')->nullable()->index()
                    ->constrained('pae_notificacoes')->nullOnDelete();
            }
        });

        Schema::table('pae_ccpae', function (Blueprint $table) {
            if (! Schema::hasColumn('pae_ccpae', 'dt_licenca_operacao')) {
                $table->date('dt_licenca_operacao')->nullable();
            }
            if (! Schema::hasColumn('pae_ccpae', 'emitido_por')) {
                $table->foreignId('emitido_por')->nullable()->index()
                    ->constrained('users')->nullOnDelete();
            }
        });

        // O default 'NOVO' nao casa com o enum ('novo'): linha criada sem status
        // explicito quebrava o cast do model.
        DB::statement("ALTER TABLE pae_protocolos ALTER COLUMN status SET DEFAULT 'novo'");
        foreach (PaeProtocoloStatus::cases() as $status) {
            DB::table('pae_protocolos')
                ->where('status', strtoupper($status->value))
                ->update(['status' => $status->value]);
        }

        EstimativaNotificacaoFeam::aplicar();
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pae_protocolos ALTER COLUMN status SET DEFAULT 'NOVO'");

        Schema::table('pae_ccpae', function (Blueprint $table) {
            $table->dropConstrainedForeignId('emitido_por');
            $table->dropColumn('dt_licenca_operacao');
        });
        Schema::table('pae_dilacoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pae_notificacao_id');
        });
        Schema::table('pae_protocolos', function (Blueprint $table) {
            $table->dropColumn(['dt_notificacao_feam', 'dt_notificacao_feam_estimada', 'ciclos_esgotados_em']);
        });
    }
};
