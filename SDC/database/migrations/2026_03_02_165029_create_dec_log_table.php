<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_log', function (Blueprint $table) {
            $table->increments('id_log'); // int unsigned auto increment

            $table->string('login', 9);

            $table->dateTime('dt_user')->nullable();

            $table->longText('acao')->nullable();

            $table->string('ip', 45)->nullable();

            $table->index('login', 'logSistema_FK');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_log');
    }
};