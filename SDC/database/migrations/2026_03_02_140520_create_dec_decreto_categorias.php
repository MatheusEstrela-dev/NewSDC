<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_decreto_categorias', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment (padrão Laravel)
            $table->string('nome', 191); // NOT NULL
            $table->string('descricao', 191)->nullable(); // pode ser null
            $table->timestamps(); // created_at e updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_decreto_categorias');
    }
};