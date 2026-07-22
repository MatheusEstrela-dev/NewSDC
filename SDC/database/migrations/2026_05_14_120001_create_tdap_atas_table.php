<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_atas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->date('dt_inicio');
            $table->date('dt_final');
            $table->text('historico')->nullable();
            $table->text('planejamento')->nullable();
            $table->string('sei', 50)->nullable();
            $table->string('siad', 50)->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ativo', 'dt_final']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_atas');
    }
};
