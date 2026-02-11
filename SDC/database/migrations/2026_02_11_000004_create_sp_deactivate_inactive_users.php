<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        DB::unprepared("DROP PROCEDURE IF EXISTS sp_deactivate_inactive_users");

        DB::unprepared("
            CREATE PROCEDURE sp_deactivate_inactive_users()
            BEGIN
                DECLARE rows_affected INT DEFAULT 0;

                START TRANSACTION;

                INSERT INTO audit_logs (user_id, event, table_name, row_id, old_values, new_values, created_at)
                SELECT
                    id,
                    'update',
                    'users',
                    id,
                    JSON_OBJECT('active', active, 'status', status),
                    JSON_OBJECT('active', 0, 'status', 'inactive'),
                    NOW()
                FROM users
                WHERE active = 1
                  AND status != 'inactive'
                  AND (last_login_at < DATE_SUB(NOW(), INTERVAL 6 MONTH) OR last_login_at IS NULL)
                  AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

                INSERT INTO user_status_histories (user_id, old_status, new_status, reason, created_at, updated_at)
                SELECT
                    id,
                    status,
                    'inactive',
                    'Desativacao automatica: inatividade superior a 6 meses',
                    NOW(),
                    NOW()
                FROM users
                WHERE active = 1
                  AND status != 'inactive'
                  AND (last_login_at < DATE_SUB(NOW(), INTERVAL 6 MONTH) OR last_login_at IS NULL)
                  AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

                UPDATE users
                SET
                    active = 0,
                    status = 'inactive',
                    updated_at = NOW()
                WHERE active = 1
                  AND status != 'inactive'
                  AND (last_login_at < DATE_SUB(NOW(), INTERVAL 6 MONTH) OR last_login_at IS NULL)
                  AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);

                SET rows_affected = ROW_COUNT();

                COMMIT;

                SELECT CONCAT('Sucesso: ', rows_affected, ' usuarios desativados.') AS result;
            END
        ");

        try {
            DB::unprepared("SET GLOBAL event_scheduler = ON");

            DB::unprepared("DROP EVENT IF EXISTS ev_daily_user_cleanup");

            DB::unprepared("
                CREATE EVENT ev_daily_user_cleanup
                ON SCHEDULE EVERY 1 DAY
                STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 3 HOUR)
                DO
                    CALL sp_deactivate_inactive_users()
            ");
        } catch (\Exception $e) {
            logger()->warning('Event scheduler nao configurado - requer privilegio SUPER. Configure manualmente em producao.');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        DB::unprepared("DROP EVENT IF EXISTS ev_daily_user_cleanup");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_deactivate_inactive_users");
    }
};
