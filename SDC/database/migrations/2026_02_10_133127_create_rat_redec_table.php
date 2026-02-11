<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('rat_redec')) return;
        Schema::create('rat_redec', function (Blueprint $table) {
            $table->id(); // bigint unsigned NOT NULL AUTO_INCREMENT
            
            // Nome da REDEC
            $table->string('nome', 100);
            
            // Sigla (ex: 1ª REDEC)
            $table->string('sigla', 10);
            
            // Timestamps (created_at e updated_at)
            $table->timestamps();

            // Configurações do Engine
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rat_redec');
    }
};
