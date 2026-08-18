<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acrescenta `documento_url` a `cisterna_ordens_servico`.
 *
 * A coluna `link_doc` do legado nao guarda caminho de arquivo, e sim **URL do
 * SEI**: `https://www.sei.mg.gov.br/sei/controlador.php?acao=procedimento_trabalhar&...`
 * em 3 das 7 ordens, e o placeholder `-` nas outras 4. Maior valor: 122
 * caracteres.
 *
 * O desenho original mandava `link_doc` para uma collection do MediaLibrary, o
 * que nao funciona: MediaLibrary guarda arquivo, nao endereco. A collection
 * receberia nada e o link do SEI se perderia na migracao.
 *
 * A collection `documento_os` **continua existindo** e nao e desperdicio: o
 * formulario novo aceita anexar o documento de verdade, o que e melhor que
 * depender de um link para sistema externo. As duas coisas coexistem por serem
 * diferentes -- referencia ao processo no SEI e o arquivo em si.
 *
 * Migration separada, aditiva, porque a tabela ja foi criada em
 * 2026_08_14_100000. Idempotente via hasColumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cisterna_ordens_servico')) {
            return;
        }

        if (Schema::hasColumn('cisterna_ordens_servico', 'documento_url')) {
            return;
        }

        Schema::table('cisterna_ordens_servico', function (Blueprint $table): void {
            // 500 com folga sobre os 122 do maior valor atual: URL do SEI
            // carrega query string e pode crescer.
            $table->string('documento_url', 500)->nullable()
                ->after('observacao')
                ->comment('Legado: link_doc. URL do processo no SEI, nao arquivo');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cisterna_ordens_servico', 'documento_url')) {
            return;
        }

        Schema::table('cisterna_ordens_servico', function (Blueprint $table): void {
            $table->dropColumn('documento_url');
        });
    }
};
