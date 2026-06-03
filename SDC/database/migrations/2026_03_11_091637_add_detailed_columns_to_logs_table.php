<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
            $table->text('context')->nullable()->change();
            $table->string('class', 255)->nullable()->after('level');
            $table->string('method', 100)->nullable()->after('class');
            $table->integer('line')->nullable()->after('method');
            $table->string('file', 500)->nullable()->after('line');
            $table->string('url', 500)->nullable()->after('file');
            $table->string('http_method', 10)->nullable()->after('url');
            $table->text('request_data')->nullable()->after('http_method');
            $table->text('stack_trace')->nullable()->after('context');
            $table->string('layer', 50)->nullable()->after('channel');
            $table->index('level');
            $table->index('class');
            $table->index('layer');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropIndex(['class']);
            $table->dropIndex(['layer']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['class', 'method', 'line', 'file', 'url', 'http_method', 'request_data', 'stack_trace', 'layer']);
        });
    }
};
