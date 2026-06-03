<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pae_forms')) {
            Schema::create('pae_forms', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('pae_protocolo_id')->nullable();
                $table->uuid('uuid_publico')->unique();
                $table->string('status', 50)->default('RASCUNHO');

                $table->string('barragem_nome', 255)->nullable();
                $table->string('emp_responsavel_nome', 255)->nullable();
                $table->string('coord_pae_nome', 255)->nullable();
                $table->string('coord_pae_email', 255)->nullable();
                $table->string('coord_mun_def_civ', 255)->nullable();
                $table->string('coord_mun_compdec', 255)->nullable();
                $table->string('metodo_construtivo', 100)->nullable();
                $table->integer('num_zas')->nullable();
                $table->smallInteger('nivel_emergencia')->nullable();

                $table->text('objetivo')->nullable();
                $table->text('contexto')->nullable();

                $table->unsignedBigInteger('municipio_id')->nullable();
                $table->unsignedBigInteger('pae_empnto_id')->nullable();

                $table->foreignId('created_by')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                $table->foreignId('updated_by')
                      ->nullable()
                      ->constrained('users')
                      ->onDelete('set null');

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('pae_protocolo_id', 'fk_forms_protocolo')
                      ->references('id')
                      ->on('pae_protocolos')
                      ->onDelete('set null');

                $table->foreign('municipio_id', 'fk_forms_municipio')
                      ->references('id')
                      ->on('municipios')
                      ->onDelete('set null');

                $table->foreign('pae_empnto_id', 'fk_forms_empnto')
                      ->references('id')
                      ->on('pae_empntos')
                      ->onDelete('set null');

                $table->index('pae_protocolo_id', 'idx_forms_protocolo');
                $table->index('municipio_id', 'idx_forms_municipio');
                $table->index('pae_empnto_id', 'idx_forms_empnto');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pae_forms');
    }
};
