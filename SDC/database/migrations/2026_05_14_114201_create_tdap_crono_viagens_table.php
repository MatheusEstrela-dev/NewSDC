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
        Schema::create('tdap_crono_viagens', function (Blueprint $table) {

            $table->id();

            $table->dateTime('data_registro');

            $table->dateTime('data_aprovacao')
                ->nullable();

            $table->string('obs')
                ->nullable();

            $table->string('obs_aprovacao')
                ->nullable();

            $table->integer('validado')
                ->nullable();

            $table->integer('crono_caminhao_id');

            $table->string('created_by', 100)
                ->nullable();

            $table->unsignedBigInteger('updated_by')
                ->nullable();

            $table->softDeletes();

            $table->timestamps();

            $table->index('crono_caminhao_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_crono_viagens');
    }
};