<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leva o contato institucional da prefeitura aos bancos que ja tinham rodado a
 * 2026_05_05_100004_create_compdec_prefeituras_table antes de ef5c83cf.
 *
 * Aquela migration ganhou essas colunas num metodo idempotente chamado do
 * proprio up(), mas o Laravel nao executa de novo uma migration ja registrada
 * em `migrations`: nesses bancos as colunas nunca chegavam e /cedec/prefeituras
 * respondia 500 (column compdec_prefeituras.email_prefeitura does not exist).
 *
 * Cada coluna e guardada por hasColumn, entao em banco novo -- onde a create
 * ja criou tudo -- esta migration nao faz nada.
 */
return new class extends Migration
{
    /** @var array<string, int> coluna => tamanho */
    private const COLUNAS = [
        'prefeito_partido'   => 60,
        'email_prefeitura'   => 255,
        'email_prefeitura_2' => 255,
        'email_prefeitura_3' => 255,
        'tel_prefeitura'     => 20,
        'tel_prefeitura_2'   => 20,
        'fax_prefeitura'     => 20,
    ];

    public function up(): void
    {
        Schema::table('compdec_prefeituras', function (Blueprint $table) {
            foreach (self::COLUNAS as $coluna => $tamanho) {
                if (! Schema::hasColumn('compdec_prefeituras', $coluna)) {
                    $coluna === 'email_prefeitura'
                        ? $table->string($coluna, $tamanho)->nullable()
                            ->comment('E-mail institucional da prefeitura; alimenta o relatorio de contatos')
                        : $table->string($coluna, $tamanho)->nullable();
                }
            }
        });
    }

    /**
     * Sem down destrutivo: em banco novo as colunas pertencem a create de
     * 2026_05_05, e remove-las aqui apagaria dado que esta migration nao criou.
     */
    public function down(): void
    {
    }
};
