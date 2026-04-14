<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('pae_notificacoes')->delete();
        DB::table('pae_analises')->delete();
        DB::table('pae_timeline')->delete();
        DB::table('pae_dilacoes')->delete();
        DB::table('pae_ccpae')->delete();
        DB::table('pae_form_itens')->delete();
        DB::table('pae_forms')->delete();
        DB::table('pae_tramit_prot')->delete();
        DB::table('pae_protocolos')->delete();
        DB::table('pae_empntos')->delete();
        DB::table('pae_empdors')->delete();
        DB::table('pae_coordenador')->delete();
        DB::table('pae_datas_ciclos')->delete();
        DB::table('pae_polif')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Dados removidos intencionalmente — restauracao nao aplicavel
    }
};
