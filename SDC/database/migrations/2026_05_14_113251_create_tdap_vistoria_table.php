<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tdap_vistoria', function (Blueprint $table) {

            $table->id();

            $table->integer('placa_id')->default(0);

            $table->string('prestador');
            $table->date('data');
            $table->string('lacre');

            $table->boolean('documento');
            $table->text('documento_obs')->nullable();

            $table->boolean('para_choque_d');
            $table->text('para_choque_d_obs')->nullable();

            $table->boolean('para_choque_t');
            $table->text('para_choque_t_obs')->nullable();

            $table->boolean('placa_d');
            $table->text('placa_d_obs')->nullable();

            $table->boolean('placa_t');
            $table->text('placa_t_obs')->nullable();

            $table->boolean('selo_placa_t');
            $table->text('selo_placa_t_obs')->nullable();

            $table->boolean('espel_ret_ex');
            $table->text('espel_ret_ex_obs')->nullable();

            $table->boolean('motor_arr_bat');
            $table->text('motor_arr_bat_obs')->nullable();

            $table->boolean('para_brisa');
            $table->text('para_brisa_obs')->nullable();

            $table->boolean('bancos_fixos');
            $table->text('bancos_fixos_obs')->nullable();

            $table->boolean('farois_d');
            $table->text('farois_d_obs')->nullable();

            $table->boolean('faroletes');
            $table->text('faroletes_obs')->nullable();

            $table->boolean('trans_farois');
            $table->text('trans_farois_obs')->nullable();

            $table->boolean('lant_set_d');
            $table->text('lant_set_d_obs')->nullable();

            $table->boolean('lant_set_e');
            $table->text('lant_set_e_obs')->nullable();

            $table->boolean('pisca_alerta');
            $table->text('pisca_alerta_obs')->nullable();

            $table->boolean('luz_re');
            $table->text('luz_re_obs')->nullable();

            $table->boolean('luz_freio');
            $table->text('luz_freio_obs')->nullable();

            $table->boolean('freio_est');
            $table->text('freio_est_obs')->nullable();

            $table->boolean('cond_pneus');
            $table->text('cond_pneus_obs')->nullable();

            $table->boolean('cond_pneus_e');
            $table->text('cond_pneus_e_obs')->nullable();

            $table->boolean('buzina');
            $table->text('buzina_obs')->nullable();

            $table->boolean('extintor');
            $table->text('extintor_obs')->nullable();

            $table->boolean('cinto_seg');
            $table->text('cinto_seg_obs')->nullable();

            $table->boolean('macanetas');
            $table->text('macanetas_obs')->nullable();

            $table->boolean('tri_rod_mac');
            $table->text('tri_rod_mac_obs')->nullable();

            $table->boolean('aus_vaz_comb');
            $table->text('aus_vaz_comb_obs')->nullable();

            $table->boolean('aus_prop_pol');
            $table->text('aus_prop_pol_obs')->nullable();

            $table->boolean('pintura_ext');
            $table->text('pintura_ext_obs')->nullable();

            $table->boolean('pintura_int');
            $table->text('pintura_int_obs')->nullable();

            $table->boolean('vazamento');
            $table->text('vazamento_obs')->nullable();

            $table->boolean('mangote');
            $table->text('mangote_obs')->nullable();

            $table->boolean('valv_expul');
            $table->text('valv_expul_obs')->nullable();

            $table->boolean('tampa_ved');
            $table->text('tampa_ved_obs')->nullable();

            $table->boolean('agua_pot');
            $table->text('agua_pot_obs')->nullable();

            $table->enum('parecer', [
                'Aprovado',
                'Reprovado'
            ]);

            $table->string('ficha');

            $table->softDeletes();

            $table->integer('deleted_by')->nullable();

            $table->timestamps();

            $table->index('placa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdap_vistoria');
    }
};