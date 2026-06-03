<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            if (!Schema::hasColumn('logs', 'request_id')) {
                $table->uuid('request_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('logs', 'parent_log_id')) {
                $table->unsignedBigInteger('parent_log_id')->nullable()->after('request_id');
            }

            if (!Schema::hasColumn('logs', 'duration_ms')) {
                $table->decimal('duration_ms', 10, 2)->nullable()->after('http_method');
            }

            if (!Schema::hasColumn('logs', 'memory_usage')) {
                $table->bigInteger('memory_usage')->nullable()->after('duration_ms');
            }

            if (!Schema::hasColumn('logs', 'tags')) {
                $table->json('tags')->nullable()->after('context');
            }

            if (!Schema::hasColumn('logs', 'event')) {
                $table->string('event', 50)->nullable()->after('layer');
            }

            if (!Schema::hasColumn('logs', 'model_id')) {
                $table->string('model_id', 100)->nullable()->after('class');
            }

            if (!Schema::hasColumn('logs', 'model_table')) {
                $table->string('model_table', 100)->nullable()->after('model_id');
            }

            $table->index('request_id');
            $table->index('event');
            $table->index('model_table');
            $table->index(['request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropIndex(['event']);
            $table->dropIndex(['model_table']);
            $table->dropIndex(['request_id', 'created_at']);

            $columns = ['request_id', 'parent_log_id', 'duration_ms', 'memory_usage', 'tags', 'event', 'model_id', 'model_table'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
