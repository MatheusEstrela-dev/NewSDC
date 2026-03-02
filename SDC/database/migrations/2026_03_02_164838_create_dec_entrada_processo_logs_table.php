<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dec_entrada_processo_logs', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('entrada_processo_id')
                ->constrained('dec_entrada_processos')
                ->onDelete('cascade');

            $table->json('entrada_processo_data');

            $table->string('action', 191)->default('updated');

            $table->timestamps();

            $table->index(
                ['entrada_processo_id', 'created_at'],
                'dec_entrada_processo_logs_entrada_processo_id_created_at_index'
            );

            $table->index('uuid', 'dec_entrada_processo_logs_uuid_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dec_entrada_processo_logs');
    }
};