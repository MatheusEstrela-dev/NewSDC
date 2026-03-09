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
        if (!Schema::hasTable('pae_empdors')) {
            Schema::create('pae_empdors', function (Blueprint $table) {
                // id bigint unsigned NOT NULL AUTO_INCREMENT
                $table->id();

                $table->string('nome', 255);
                
                // cnpj UNIQUE
                $table->string('cnpj', 18)->nullable()->unique('cnpj');
                
                $table->string('status', 50)->default('ATIVO');

                // created_at e updated_at
                $table->timestamps();
                
                // deleted_at (Para suporte ao SoftDeletes do Eloquent)
                $table->softDeletes();

                // Índice para busca por status
                $table->index('status', 'idx_empdors_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pae_empdors');
    }
};