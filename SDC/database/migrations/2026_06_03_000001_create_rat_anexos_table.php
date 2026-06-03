<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rat_anexos', function (Blueprint $table) {
            $table->id();
            $table->string('rat_id');
            $table->foreign('rat_id')->references('id')->on('rats')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nome_original');
            $table->string('nome_arquivo');
            $table->string('mime_type')->nullable();
            $table->bigInteger('tamanho_bytes')->default(0);
            $table->string('path');
            $table->timestamps();

            $table->index('rat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_anexos');
    }
};
