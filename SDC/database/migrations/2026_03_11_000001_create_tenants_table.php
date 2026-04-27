<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();           // identificador único (ex: "compdec-rj")
            $table->string('database')->nullable();     // nome do banco para multi-db tenancy
            $table->string('dominio')->nullable();      // domínio vinculado ao tenant
            $table->boolean('ativo')->default(true);
            $table->jsonb('config')->nullable();         // configurações extras por tenant
            $table->timestamps();
            $table->index('config', 'idx_tenants_config', 'gin');
            $table->softDeletes();

            $table->index('slug');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
