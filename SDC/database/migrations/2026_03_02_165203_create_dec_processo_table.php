<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_processo', function (Blueprint $table) {
            $table->id('id_processo');

            $table->unsignedSmallInteger('ano')->nullable();
            $table->date('dt_entrada')->nullable();
            $table->integer('num_processo')->nullable()->unique();
            $table->integer('id_municipio')->nullable();
            $table->string('num_dec_mun', 15)->nullable();
            $table->date('dt_dec_mun')->nullable();
            $table->string('dec_vigencia', 10)->nullable();
            $table->integer('id_cobrade')->nullable();
            $table->date('dt_vencimento')->nullable();
            $table->integer('status')->nullable();
            $table->integer('id_funcionario')->nullable();
            $table->string('num_dec_homo', 15)->nullable();
            $table->date('dt_pub_dec_homo')->nullable();
            $table->string('num_dt_port_dec_rec', 20)->nullable();
            $table->string('num_dt_dou', 20)->nullable();
            $table->integer('populacao')->nullable();

            $table->decimal('val_pib', 16, 2)->nullable();
            $table->decimal('val_orcamento', 16, 2)->nullable();
            $table->decimal('val_arrecadacao', 16, 2)->nullable();
            $table->decimal('val_rec_anual', 16, 2)->nullable();
            $table->decimal('val_rec_mensal', 16, 2)->nullable();

            $table->string('telefone', 20)->nullable();
            $table->string('email', 45)->nullable();
            $table->decimal('vl_total', 16, 2)->nullable();

            $table->integer('morto')->nullable();
            $table->integer('ferido')->nullable();
            $table->integer('enfermo')->nullable();
            $table->integer('desabrigado')->nullable();
            $table->integer('desalojado')->nullable();
            $table->integer('outro')->nullable();
            $table->integer('afetado')->nullable();

            $table->integer('mat_pub_saude_destr')->nullable();
            $table->integer('mat_pub_saude_danif')->nullable();
            $table->decimal('val_mat_pub_saude', 16, 2)->nullable();

            $table->integer('mat_pub_ensino_destr')->nullable();
            $table->integer('mat_pub_ensino_danif')->nullable();
            $table->decimal('val_mat_pub_ensino', 16, 2)->nullable();

            $table->integer('mat_pub_outro_destr')->nullable();
            $table->integer('mat_pub_outro_danif')->nullable();
            $table->decimal('val_mat_pub_outro', 16, 2)->nullable();

            $table->integer('mat_pub_com_destr')->nullable();
            $table->integer('mat_pub_com_danif')->nullable();
            $table->decimal('val_mat_pub_com', 16, 2)->nullable();

            $table->integer('mat_unid_hab_destr')->nullable();
            $table->integer('mat_unid_hab_danif')->nullable();
            $table->decimal('val_mat_unid_hab', 16, 2)->nullable();

            $table->integer('mat_obr_infr_pub_destr')->nullable();
            $table->integer('mat_obr_infr_pub_danif')->nullable();
            $table->decimal('val_mat_obr_infr_pub', 16, 2)->nullable();

            $table->integer('agua_pop_atingida')->nullable();
            $table->integer('solo_pop_atingida')->nullable();
            $table->integer('ar_pop_atingida')->nullable();
            $table->integer('incendio_pop_atingida')->nullable();

            $table->decimal('val_eco_pub_saude', 16, 2)->nullable();
            $table->decimal('val_eco_pub_agua', 16, 2)->nullable();
            $table->decimal('val_eco_pub_esgoto', 16, 2)->nullable();
            $table->decimal('val_eco_pub_lixo', 16, 2)->nullable();
            $table->decimal('val_eco_pub_praga', 16, 2)->nullable();
            $table->decimal('val_eco_pub_energia', 16, 2)->nullable();
            $table->decimal('val_eco_pub_telec', 16, 2)->nullable();
            $table->decimal('val_eco_pub_transp', 16, 2)->nullable();
            $table->decimal('val_eco_pub_comb', 16, 2)->nullable();
            $table->decimal('val_eco_pub_segur', 16, 2)->nullable();
            $table->decimal('val_eco_pub_ensino', 16, 2)->nullable();
            $table->decimal('val_eco_pub', 16, 2)->nullable();

            $table->decimal('val_eco_priv_agricul', 16, 2)->nullable();
            $table->decimal('val_eco_priv_pecuaria', 16, 2)->nullable();
            $table->decimal('val_eco_priv_industria', 16, 2)->nullable();
            $table->decimal('val_eco_priv_servico', 16, 2)->nullable();
            $table->decimal('val_val_eco_priv', 16, 2)->nullable();

            $table->boolean('stat_reconhecido')->default(0);
            $table->boolean('stat_arquivo')->default(0);
            $table->boolean('stat_homologa')->default(0);
            $table->boolean('stat_analise')->default(0);

            $table->index('id_cobrade', 'fk_cobrade_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_processo');
    }
};