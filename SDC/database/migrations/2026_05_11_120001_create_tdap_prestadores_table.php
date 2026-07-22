<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdap_prestadores', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 18)->unique();
            $table->string('cpf', 14)->nullable();
            $table->string('nome', 150);
            $table->string('representante', 150)->nullable();
            $table->string('email', 150);
            $table->string('tel1', 20)->nullable();
            $table->string('tel2', 20)->nullable();
            $table->string('endereco', 200)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 10)->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_prestadores');
    }
};
