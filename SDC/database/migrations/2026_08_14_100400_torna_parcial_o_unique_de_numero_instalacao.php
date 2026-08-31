<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Torna parcial o UNIQUE de `cisterna_vistorias.numero_instalacao`, ignorando
 * registros excluidos logicamente.
 *
 * O indice nasceu total, e isso discorda do codigo: o
 * `NumeracaoInstalacaoService::numeroEstaLivre()` consulta pelo model, que ja
 * exclui soft-deleted, entao ele **assume** o comportamento parcial. Com o
 * indice total, o efeito e:
 *
 *   1. vistoria e excluida logicamente, mas segue ocupando o numero no banco
 *   2. o service consulta, nao ve o registro e responde "numero livre"
 *   3. o INSERT estoura violacao de unicidade crua, em vez da
 *      ValidationException tratada que o usuario deveria ver
 *
 * Ou seja: a mensagem de erro certa ("este QR Code ja esta vinculado a outra
 * cisterna") nunca apareceria, e sairia um 500.
 *
 * A escolha por parcial tambem alinha com o outro unique do modulo,
 * `cisterna_beneficiarios_cpf_unq`, que ja nasceu com `deleted_at IS NULL`.
 * Ter dois criterios diferentes de soft-delete no mesmo modulo e pegadinha
 * para quem vier depois.
 *
 * A troca e segura mesmo com dado: um indice parcial e menos restritivo que o
 * total, entao nenhuma linha existente pode viola-lo.
 */
return new class extends Migration
{
    private const INDICE_TOTAL = 'cisterna_vistorias_numero_instalacao_unique';

    private const INDICE_PARCIAL = 'cisterna_vistorias_numero_instalacao_unq';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('cisterna_vistorias')) {
            return;
        }

        // O UNIQUE veio de $table->unique(), entao e constraint e nao apenas
        // indice: precisa sair por ALTER TABLE.
        DB::statement('ALTER TABLE cisterna_vistorias DROP CONSTRAINT IF EXISTS '.self::INDICE_TOTAL);
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_TOTAL);

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDICE_PARCIAL.' '
            .'ON cisterna_vistorias (numero_instalacao) '
            .'WHERE numero_instalacao IS NOT NULL AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! Schema::hasTable('cisterna_vistorias')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_PARCIAL);

        DB::statement(
            'ALTER TABLE cisterna_vistorias ADD CONSTRAINT '.self::INDICE_TOTAL.' '
            .'UNIQUE (numero_instalacao)'
        );
    }
};
