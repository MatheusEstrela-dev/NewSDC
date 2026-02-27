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
        Schema::create('plantoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantonista_id')->constrained('users')->onDelete('cascade');
            $table->string('plantonista_nome');
            $table->date('data');
            $table->string('periodo');
            $table->string('status')->default('ATIVO');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantoes');
    }
};
