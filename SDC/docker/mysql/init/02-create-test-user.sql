-- ============================================================================
-- SDC - Script de Inicialização MySQL
-- NOTA: Este script NÃO cria o usuário diretamente porque a tabela users
-- é criada pelas migrations do Laravel. O usuário de teste é criado pelo
-- DatabaseSeeder após as migrations serem executadas.
-- 
-- Para garantir que o usuário existe, execute:
-- php artisan migrate --force
-- php artisan db:seed --force
-- 
-- Credenciais do usuário de teste:
-- CPF: 12345678900 (sem formatação: 12345678900)
-- Senha: password
-- ============================================================================

-- Este script serve apenas como documentação
-- O usuário será criado pelo DatabaseSeeder do Laravel

SELECT 'ℹ️  Usuário de teste será criado pelo DatabaseSeeder' AS Status;
SELECT '   Execute: php artisan migrate --force && php artisan db:seed --force' AS Info;

