<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 27 itens estruturais + obs cada.
     *
     * @var array<int, string>
     */
    private array $itensEstruturais = [
        'documento', 'para_choque_d', 'para_choque_t', 'placa_d', 'placa_t', 'selo_placa_t',
        'espel_ret_ex', 'motor_arr_bat', 'para_brisa', 'bancos_fixos', 'farois_d',
        'faroletes', 'trans_farois', 'lant_set_d', 'lant_set_e', 'pisca_alerta',
        'luz_re', 'luz_freio', 'freio_est', 'cond_pneus', 'cond_pneus_e',
        'buzina', 'extintor', 'cinto_seg', 'macanetas', 'tri_rod_mac',
        'aus_vaz_comb', 'aus_prop_pol',
    ];

    /**
     * 7 itens do tanque + obs cada.
     *
     * @var array<int, string>
     */
    private array $itensTanque = [
        'pintura_ext', 'pintura_int', 'vazamento', 'mangote',
        'valv_expul', 'tampa_ved', 'agua_pot',
    ];

    public function up(): void
    {
        $estruturais = $this->itensEstruturais;
        $tanque = $this->itensTanque;

        Schema::create('tdap_vistorias', function (Blueprint $table) use ($estruturais, $tanque) {
            $table->id();

            $table->string('nome', 150)->comment('Nome do vistoriador');
            $table->string('edital', 50)->nullable();
            $table->string('lote', 30)->nullable();

            $table->foreignId('placa_id')
                ->constrained('tdap_caminhoes')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Caminhao vistoriado');

            $table->string('modelo', 50)->nullable();
            $table->string('cor', 30)->nullable();
            $table->date('data')->comment('Data da vistoria');
            $table->string('ano', 4)->nullable();
            $table->decimal('capacidade', 8, 2)->comment('Capacidade do tanque (m3) no momento da vistoria');

            foreach ($estruturais as $campo) {
                $table->boolean($campo)->default(false);
                $table->text("{$campo}_obs")->nullable();
            }

            foreach ($tanque as $campo) {
                $table->boolean($campo)->default(false);
                $table->text("{$campo}_obs")->nullable();
            }

            $table->string('parecer', 20)->default('aprovada')
                ->comment('aprovada | reprovada (CHECK constraint)');
            $table->string('ficha', 50)->nullable()->comment('Numero do laudo/ficha');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que registrou a vistoria');

            $table->text('observacoes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['placa_id', 'data']);
            $table->index(['parecer', 'data']);
        });

        // Constraint CHECK para o parecer — apenas PostgreSQL
        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement("
                ALTER TABLE tdap_vistorias
                ADD CONSTRAINT chk_tdap_vistorias_parecer
                CHECK (parecer IN ('aprovada', 'reprovada'))
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tdap_vistorias');
    }
};
