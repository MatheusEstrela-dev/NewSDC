<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pae_form_anexos', function (Blueprint $table) {
            $table->string('categoria', 20)->default('outros')->after('descricao');
            $table->index(['pae_form_id', 'categoria'], 'idx_pae_form_anexos_form_categoria');
        });
    }

    public function down(): void
    {
        Schema::table('pae_form_anexos', function (Blueprint $table) {
            $table->dropIndex('idx_pae_form_anexos_form_categoria');
            $table->dropColumn('categoria');
        });
    }
};
