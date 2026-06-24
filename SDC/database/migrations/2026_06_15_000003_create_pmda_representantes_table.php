<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pmda_representantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pmda_comunidade_id')->constrained('pmda_comunidades')->cascadeOnDelete();
            $table->string('nome', 100);
            $table->string('tel', 20)->nullable();
            $table->string('email', 110)->nullable();
            $table->string('cpf', 14)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmda_representantes');
    }
};
