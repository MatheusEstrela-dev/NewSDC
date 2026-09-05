<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compdec_prefeituras')) {
            $this->adicionarColunasContatoInstitucional();

            return;
        }

        Schema::create('compdec_prefeituras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipio_id')
                ->unique()
                ->constrained('municipios')
                ->cascadeOnDelete()
                ->comment('Cada municipio tem uma unica prefeitura');

            // Dados do prefeito
            $table->string('prefeito_nome', 255)->nullable();
            $table->string('prefeito_telefone', 20)->nullable();
            $table->string('prefeito_celular', 20)->nullable();
            $table->string('prefeito_email', 255)->nullable();
            $table->string('prefeito_partido', 60)->nullable();

            // Contato institucional da prefeitura, distinto do contato do prefeito.
            // email_prefeitura e o campo que alimenta o relatorio de contatos do
            // modulo Cedec; no legado ele mora em cedec_municipio.email.
            $table->string('email_prefeitura', 255)->nullable()
                ->comment('E-mail institucional da prefeitura; alimenta o relatorio de contatos');
            $table->string('email_prefeitura_2', 255)->nullable();
            $table->string('email_prefeitura_3', 255)->nullable();
            $table->string('tel_prefeitura', 20)->nullable();
            $table->string('tel_prefeitura_2', 20)->nullable();
            $table->string('fax_prefeitura', 20)->nullable();

            // Endereco
            $table->text('endereco')->nullable();
            $table->string('bairro', 120)->nullable();
            $table->string('cep', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // INSS
            $table->boolean('inss_tem_cobranca')->default(false);
            $table->decimal('inss_aliquota', 5, 2)->nullable()
                ->comment('% da aliquota INSS, ex: 2.50');
            $table->string('inss_lei_cobranca', 120)->nullable();
            $table->string('inss_responsavel', 255)->nullable();

            // Rastreabilidade ETL
            $table->unsignedBigInteger('legacy_id')->nullable()
                ->comment('ID original do cedec_prefeitura.id');

            $table->timestamps();
            $table->softDeletes();

            $table->index('legacy_id');
        });
    }

    /**
     * compdec_prefeituras ja existe em todo banco de desenvolvimento e producao, entao
     * o Schema::create acima nunca mais roda nesses bancos. Este metodo acrescenta as
     * mesmas colunas de contato institucional via Schema::table, mantendo UMA migration
     * consolidada -- sem criar add_* -- que serve tanto para instalacao nova quanto para
     * banco ja migrado. Cada coluna e guardada por hasColumn, entao rodar de novo e
     * inofensivo.
     */
    private function adicionarColunasContatoInstitucional(): void
    {
        Schema::table('compdec_prefeituras', function (Blueprint $table) {
            if (! Schema::hasColumn('compdec_prefeituras', 'prefeito_partido')) {
                $table->string('prefeito_partido', 60)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'email_prefeitura')) {
                $table->string('email_prefeitura', 255)->nullable()
                    ->comment('E-mail institucional da prefeitura; alimenta o relatorio de contatos');
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'email_prefeitura_2')) {
                $table->string('email_prefeitura_2', 255)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'email_prefeitura_3')) {
                $table->string('email_prefeitura_3', 255)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'tel_prefeitura')) {
                $table->string('tel_prefeitura', 20)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'tel_prefeitura_2')) {
                $table->string('tel_prefeitura_2', 20)->nullable();
            }
            if (! Schema::hasColumn('compdec_prefeituras', 'fax_prefeitura')) {
                $table->string('fax_prefeitura', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compdec_prefeituras');
    }
};
