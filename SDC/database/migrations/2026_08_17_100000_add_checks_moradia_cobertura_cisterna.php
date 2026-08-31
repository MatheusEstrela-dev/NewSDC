<?php

declare(strict_types=1);

use App\Modules\Cisterna\Enums\CoberturaTelhado;
use App\Modules\Cisterna\Enums\TipoMoradia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CHECK de tipo_moradia e cobertura_telhado em cisterna_beneficiarios.
 *
 * Separada da migration do dominio de proposito: os valores validos so foram
 * conhecidos DEPOIS da extracao do legado, quando o SELECT DISTINCT sobre
 * cisterna_legado_raw.doc revelou o que existe em producao (spec secao 4.3).
 * Empilhar aqui e correto -- nao e correcao de erro, e informacao que nao
 * existia quando a tabela foi criada.
 *
 * Aditiva e idempotente: nao derruba nem recria coluna.
 */
return new class extends Migration
{
    public function up(): void
    {
        // CHECK com lista de valores e especifico do Postgres. Em sqlite o
        // ALTER TABLE ADD CONSTRAINT nem existe.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Normaliza o que ja estiver fora da lista antes de travar, senao o
        // ADD CONSTRAINT falha validando as linhas existentes. O valor original
        // nao se perde: continua em cisterna_legado_raw.doc.
        DB::table('cisterna_beneficiarios')
            ->whereNotNull('tipo_moradia')
            ->whereNotIn('tipo_moradia', TipoMoradia::valores())
            ->update(['tipo_moradia' => null]);

        DB::table('cisterna_beneficiarios')
            ->whereNotNull('cobertura_telhado')
            ->whereNotIn('cobertura_telhado', CoberturaTelhado::valores())
            ->update(['cobertura_telhado' => null]);

        $this->adicionarCheck('tipo_moradia', TipoMoradia::valores());
        $this->adicionarCheck('cobertura_telhado', CoberturaTelhado::valores());
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE cisterna_beneficiarios DROP CONSTRAINT IF EXISTS cisterna_beneficiarios_tipo_moradia_check');
        DB::statement('ALTER TABLE cisterna_beneficiarios DROP CONSTRAINT IF EXISTS cisterna_beneficiarios_cobertura_telhado_check');
    }

    /**
     * @param  array<int, string>  $valores
     */
    private function adicionarCheck(string $coluna, array $valores): void
    {
        $constraint = "cisterna_beneficiarios_{$coluna}_check";

        // Postgres nao tem ADD CONSTRAINT IF NOT EXISTS; o DROP antes e o que
        // torna a migration repetivel sem estourar duplicate_object.
        DB::statement("ALTER TABLE cisterna_beneficiarios DROP CONSTRAINT IF EXISTS {$constraint}");

        $lista = implode(', ', array_map(fn (string $v): string => "'{$v}'", $valores));

        DB::statement(
            "ALTER TABLE cisterna_beneficiarios ADD CONSTRAINT {$constraint} "
            ."CHECK ({$coluna} IS NULL OR {$coluna} IN ({$lista}))"
        );
    }
};
