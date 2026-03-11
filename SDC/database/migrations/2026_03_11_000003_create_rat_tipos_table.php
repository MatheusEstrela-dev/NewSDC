<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela rat_tipos — classificação dos tipos de ocorrência RAT.
 *
 * Substitui o campo livre nat_codigo em rat_relato_dados_gerais por uma FK
 * tipada, garantindo integridade referencial e validação de domínio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rat_tipos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 10)->unique()
                  ->comment('Código curto do tipo (ex: EMG, VST, BSC)');

            $table->string('descricao', 255)
                  ->comment('Descrição legível (ex: Emergência Médica, Vistoria Técnica)');

            $table->enum('categoria', ['emergencia', 'vistoria', 'busca', 'resgate', 'outros'])
                  ->default('outros')
                  ->comment('Categoria principal do tipo de ocorrência');

            $table->boolean('ativo')->default(true)
                  ->comment('Tipo disponível para seleção em novos registros');

            $table->timestamps();
            $table->softDeletes();

            $table->index('categoria');
            $table->index('ativo');

            $table->engine    = 'InnoDB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });

        // Seed inicial dos tipos comuns de ocorrência da Defesa Civil
        DB::table('rat_tipos')->insert([
            ['codigo' => 'EMG',  'descricao' => 'Emergência Médica / Pré-Hospitalar',  'categoria' => 'emergencia', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'INC',  'descricao' => 'Incêndio em Edificação',               'categoria' => 'emergencia', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ACT',  'descricao' => 'Acidente de Trânsito',                 'categoria' => 'emergencia', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'VST',  'descricao' => 'Vistoria Técnica de Risco',            'categoria' => 'vistoria',   'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'BSC',  'descricao' => 'Busca e Salvamento em Estruturas',     'categoria' => 'busca',      'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'BSA',  'descricao' => 'Busca e Salvamento Aquático',          'categoria' => 'busca',      'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'RSG',  'descricao' => 'Resgate em Altura',                    'categoria' => 'resgate',    'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'DES',  'descricao' => 'Deslizamento / Desmoronamento',        'categoria' => 'emergencia', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ENX',  'descricao' => 'Enchente / Inundação',                 'categoria' => 'emergencia', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'OUT',  'descricao' => 'Outros',                               'categoria' => 'outros',     'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rat_tipos');
    }
};
