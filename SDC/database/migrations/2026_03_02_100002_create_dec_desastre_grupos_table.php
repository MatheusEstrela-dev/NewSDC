<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_desastre_grupos', function (Blueprint $table) {
            $table->id();

            $table->string('titulo')->nullable();
            $table->string('numero')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_desastre_grupos');
    }
};