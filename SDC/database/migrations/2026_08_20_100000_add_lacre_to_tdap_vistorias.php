<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numero do lacre aplicado no tanque no fim da vistoria. A ficha de campo
 * registra esse numero (e o que amarra o tanque vistoriado ao caminhao que
 * roda na viagem), mas a tabela nao tinha coluna para ele — o dado ficava
 * solto em `observacoes`.
 *
 * Nullable: as vistorias ja gravadas nao tem lacre informado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdap_vistorias', function (Blueprint $table): void {
            $table->string('lacre', 30)
                ->nullable()
                ->after('ficha')
                ->comment('Numero do lacre do tanque');
        });
    }

    public function down(): void
    {
        Schema::table('tdap_vistorias', function (Blueprint $table): void {
            $table->dropColumn('lacre');
        });
    }
};
