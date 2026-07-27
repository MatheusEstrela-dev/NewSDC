<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Arquivo morto do RAT legado (tabela de origem `com_rat` do sistema antigo,
 * herdada de um componente Joomla `com_*`). Importado como somente-leitura para
 * o Postgres do NewSDC.
 *
 * Prefixo `legado_rat_*` para identificacao imediata do que e dado legado.
 * Preserva o `id` legado como PK (sem auto-incremento) para manter referencias
 * estaveis com o sistema de origem. As colunas de texto livre sao `text` para
 * absorver dados do legado que excediam os limites VARCHAR originais (o MyISAM
 * antigo nao era estrito). Duas tabelas de apoio pequenas e fixas
 * (`legado_rat_ocorrencia` e `legado_rat_alvo`) sao semeadas aqui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legado_rat_ocorrencia', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary()->comment('Id legado com_rat_ocorrencia');
            $table->string('cod', 10)->nullable()->comment('Codigo da ocorrencia (DC00xx)');
            $table->string('descricao', 150)->nullable()->comment('Descricao da ocorrencia');
        });

        Schema::create('legado_rat_alvo', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary()->comment('Id legado com_rat_alvo');
            $table->string('alvo', 80)->nullable()->comment('Nome do alvo da ocorrencia');
        });

        Schema::create('legado_rat', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->comment('Id legado com_rat');
            $table->string('num_ocorrencia', 20)->default('0')->comment('Numero da ocorrencia (protocolo legado)');
            $table->timestamp('dt_ocorrencia')->nullable()->comment('Data da ocorrencia');
            $table->unsignedBigInteger('municipio_id')->nullable()->comment('FK cedec_municipio');
            $table->unsignedBigInteger('operador_id')->nullable()->comment('Operador legado (users antigos)');
            $table->string('operador_nome', 150)->nullable();
            $table->unsignedInteger('ocorrencia_id')->nullable()->comment('FK legado_rat_ocorrencia');
            $table->unsignedInteger('alvo_id')->nullable()->comment('FK legado_rat_alvo');
            $table->unsignedInteger('cobrade_id')->nullable()->comment('FK dec_cobrade');
            $table->text('lugar_descricao')->nullable();
            $table->text('envolvidos')->nullable();
            $table->text('nome_operacao')->nullable();
            $table->text('endereco')->nullable();
            $table->string('numero', 50)->nullable();
            $table->text('bairro')->nullable();
            $table->string('estado', 80)->nullable();
            $table->text('referencia')->nullable();
            $table->string('cep', 20)->nullable();
            $table->text('acoes')->nullable()->comment('Historico/acoes executadas (HTML legado)');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('num_ocorrencia');
            $table->index('dt_ocorrencia');
            $table->index('municipio_id');
            $table->index('cobrade_id');
            $table->index('ocorrencia_id');
        });

        $this->seedOcorrencias();
        $this->seedAlvos();
    }

    public function down(): void
    {
        Schema::dropIfExists('legado_rat');
        Schema::dropIfExists('legado_rat_alvo');
        Schema::dropIfExists('legado_rat_ocorrencia');
    }

    /**
     * Codigos DC0001..DC0044 do legado com_rat_ocorrencia. O id segue a ordem de
     * insercao original (DC000N => id N), pois `com_rat.ocorrencia_id` referencia
     * exatamente esse id sequencial.
     */
    private function seedOcorrencias(): void
    {
        $codigos = [
            'REUNIAO COMUNITARIA OU COM ENTIDADES DIVERSAS',
            'REUNIAO COM ASSOCIACAO DE MORADORES',
            'REUNIAO COM OUTROS TIPOS DE ENTIDADES',
            'ACAO CIVICO-SOCIAL (ACISO)',
            'PARTICIPACAO EM REUNIOES DE MEIO AMBIENTE',
            'OUTRAS ACOES DE DEFESA SOCIAL (DISCRIMINAR NO HISTORICO)',
            'VISTORIA EM RISCO DE DESABAMENTO/DESMORONAMENTO',
            'VISTORIA EM RISCO DE DESLIZAMENTO / CORRIDA DE MASSA',
            'VISTORIA EM RISCO DE ROMPIMENTO DE BARRAGENS',
            'VISTORIA EM RISCO DE INUNDACAO / ALAGAMENTO / ENXURRADA',
            'OUTROS TIPOS DE VISTORIAS OPERACIONAIS DIVERSAS',
            'DEMONSTRACOES PROFISSIONAIS',
            'ATIVIDADES EM ESTANDES',
            'VOO DE DEMONSTRACAO',
            'OUTROS TIPOS DE DEMONSTRACOES PROFISSIONAIS',
            'PALESTRA DE DEFESA CIVIL',
            'OUTROS TIPOS DE PALESTRAS',
            'PROJETOS SOCIAIS',
            'ABASTECIMENTO DE AGUA',
            'DISTRIBUICAO DE MATERIAIS',
            'EVACUACAO DE AREAS DE RISCO',
            'OUTROS TIPOS DE ACOES DE APOIO A COMUNIDADE',
            'ABALOS SISMICOS (TREMORES DE TERRA)',
            'DESCARGAS ATMOSFERICAS (RAIOS)',
            'VENDAVAL',
            'CHUVAS INTENSAS',
            'GRANIZO',
            'QUEDAS TOMBAMENTOS ROLAMENTOS',
            'DESLIZAMENTOS CORRIDA DE MASSA',
            'INUNDACOES / ALAGAMENTOS / ENXURRADAS',
            'OUTROS TIPOS DE DESASTRES/EVENTOS DE GRANDE IMPACTO DE ORIGEM NATURAL',
            'ROMPIMENTO E COLAPSO DE BARRAGENS',
            'COLAPSO DE EDIFICACOES',
            'OUTROS TIPOS DE DESASTRES / EVENTOS DE GRANDE IMPACTO DE ORIGEM TECNOLOGICA',
            'MAPEAMENTO DE AREAS DE RISCO',
            'MONITORAMENTO',
            'ORIENTACAO A POPULACAO RESIDENTE EM AREAS DE RISCO',
            'ACOES DE PREPARACAO ENVOLVENDO DEFESA CIVIL ESTADUAL/MUNICIPAL',
            'OUTROS TIPOS DE ACOES DA GESTAO DO RISCO DE DESASTRE',
            'COMUNICACOES, DENUNCIAS, RECLAMACOES E SOLICITACOES DIVERSAS',
            'APOIO A ORGAOS FEDERAIS',
            'APOIO A ORGAOS ESTADUAIS',
            'APOIO A ORGAOS MUNICIPAIS',
            'APOIO A EMPRESAS / INSTITUICOES PRIVADAS',
        ];

        $rows = [];
        foreach ($codigos as $i => $descricao) {
            $id = $i + 1;
            $rows[] = [
                'id' => $id,
                'cod' => sprintf('DC%04d', $id),
                'descricao' => $descricao,
            ];
        }

        DB::table('legado_rat_ocorrencia')->insert($rows);
    }

    /**
     * Alvos do legado com_rat_alvo, na ordem original de insercao (id 1..19).
     */
    private function seedAlvos(): void
    {
        $alvos = [
            'PESSOA',
            'INSTITUICAO FILANTROPICA / ONG',
            'CONDOMINIO',
            'COOPERATIVA',
            'DEPOSITO',
            'EMBARCACAO AEREA / NAUTICA / TERRESTRE',
            'ESTABELECIMENTO COMERCIAL / SERVICOS',
            'ESTABELECIMENTO INDUSTRIAL / PRODUCAO / EXTRACAO',
            'LOCAL / ESTABELECIMENTO DE LAZER / CULTURA',
            'RESIDENCIA / IMOVEL RURAL',
            'INSTITUICAO DE ENSINO',
            'INSTITUICAO FINANCEIRA',
            'INSTITUICAO PUBLICA',
            'RESIDENCIA PLURIFAMILIAR / HOSPEDAGEM',
            'RESIDENCIA UNIFAMILIAR URBANA',
            'SERVICO DE SAUDE',
            'SINDICATO / ASSOCIACAO DE CLASSE',
            'AREA / EDIFICACAO ESPECIAL',
            'UNIDADE DE CONSERVACAO DA NATUREZA',
        ];

        $rows = [];
        foreach ($alvos as $i => $alvo) {
            $rows[] = ['id' => $i + 1, 'alvo' => $alvo];
        }

        DB::table('legado_rat_alvo')->insert($rows);
    }
};
