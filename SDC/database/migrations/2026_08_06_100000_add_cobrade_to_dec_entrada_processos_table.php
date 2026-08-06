<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Passa a guardar o CODIGO COBRADE (padrao nacional) no proprio processo.
 *
 * MOTIVO: `tipo_desastre_id` guarda o id POSICIONAL do array PHP
 * app/Enums/classificacao_desastres.php, que nao tem significado fora da
 * aplicacao - e pior, nao bate com os ids de `dec_cobrade` (33 dos 65 diferem).
 * Qualquer leitura do banco (SQL direto, Power BI, integracao) precisava do
 * array PHP para saber que o id 24 significa 1.3.2.1.4.
 *
 * A chave NAO muda: `tipo_desastre_id` continua sendo o vinculo usado por
 * filtros, resources e accessors. A coluna nova e derivada, preenchida no
 * backfill e mantida pelo hook `saving` do model Processo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            // varchar(11): o codigo tem 5 segmentos ("1.3.2.1.4" = 9 chars);
            // 11 deixa folga sem virar campo livre.
            $table->string('cobrade', 11)->nullable()->after('tipo_desastre_id');
        });

        // Indice para permitir fatiar por classificacao direto no banco (Power BI,
        // relatorios) sem varrer a tabela.
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->index('cobrade', 'idx_proc_cobrade');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('dec_entrada_processos', function (Blueprint $table) {
            $table->dropIndex('idx_proc_cobrade');
            $table->dropColumn('cobrade');
        });
    }

    /**
     * Preenche o codigo dos processos existentes a partir do enum.
     *
     * Um UPDATE por codigo (nao por processo): sao no maximo 65 comandos,
     * independente do volume da tabela.
     */
    private function backfill(): void
    {
        foreach ($this->mapaIdParaCodigo() as $id => $codigo) {
            DB::table('dec_entrada_processos')
                ->where('tipo_desastre_id', $id)
                ->whereNull('cobrade')
                ->update(['cobrade' => $codigo]);
        }
    }

    /**
     * @return array<int, string> id posicional do enum => codigo COBRADE
     */
    private function mapaIdParaCodigo(): array
    {
        $arquivo = app_path('Enums/classificacao_desastres.php');

        if (! is_file($arquivo)) {
            return [];
        }

        $mapa = [];

        foreach ((array) include $arquivo as $item) {
            $id = (int) ($item['id'] ?? 0);
            $codigo = trim((string) ($item['cobrade'] ?? ''));

            if ($id > 0 && $codigo !== '') {
                $mapa[$id] = $codigo;
            }
        }

        return $mapa;
    }
};
