-- ============================================================================
-- SDC - Script de Inicialização MySQL
-- Cria usuário de teste para autenticação na middleware
-- Executado automaticamente na primeira inicialização do container
-- ============================================================================

-- Garantir que o banco de dados existe
CREATE DATABASE IF NOT EXISTS `sdc` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Usar o banco de dados
USE `sdc`;

-- Remover usuário existente se houver (para garantir configuração limpa)
DROP USER IF EXISTS 'sdc'@'%';
DROP USER IF EXISTS 'sdc'@'localhost';

-- Criar usuário de teste para autenticação na middleware
-- Este usuário será usado pela aplicação Laravel para conectar ao banco
CREATE USER 'sdc'@'%' IDENTIFIED BY 'secret';
CREATE USER 'sdc'@'localhost' IDENTIFIED BY 'secret';

-- Garantir que o usuário root também pode acessar de qualquer host (para desenvolvimento)
-- Isso é necessário para a bridge de dev funcionar corretamente
DROP USER IF EXISTS 'root'@'%';
CREATE USER 'root'@'%' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;

-- Conceder todas as permissões ao usuário de teste no banco sdc
-- Essas permissões são necessárias para a aplicação Laravel funcionar corretamente
GRANT ALL PRIVILEGES ON `sdc`.* TO 'sdc'@'%';
GRANT ALL PRIVILEGES ON `sdc`.* TO 'sdc'@'localhost';

-- Conceder permissões específicas necessárias para a aplicação
-- Inclui todas as operações que o Laravel pode precisar
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, 
      CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, 
      SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER 
      ON `sdc`.* TO 'sdc'@'%';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, 
      CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, 
      SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER 
      ON `sdc`.* TO 'sdc'@'localhost';

-- Aplicar as mudanças
FLUSH PRIVILEGES;

-- Log de sucesso
SELECT '✅ Usuário de teste criado com sucesso!' AS Status;
SELECT '   Usuário: sdc' AS Info;
SELECT '   Banco: sdc' AS Info;
SELECT '   Host: % e localhost' AS Info;

