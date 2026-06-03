-- --------------------------------------------------------
-- Servidor:                     200.198.29.227
-- Versão do servidor:           8.0.31 - MySQL Community Server - GPL
-- OS do Servidor:               Linux
-- HeidiSQL Versão:              12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Copiando estrutura do banco de dados para dbsdc
CREATE DATABASE IF NOT EXISTS `dbsdc` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `dbsdc`;

-- Copiando estrutura para tabela dbsdc.tdap_historico
CREATE TABLE IF NOT EXISTS `tdap_historico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dt_historico` datetime NOT NULL COMMENT 'Data Registro',
  `setor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Seção',
  `model` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Seção',
  `texto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Texto do Evento/ Setor',
  `arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `municipio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nao_aplica` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_historico: 3 rows
/*!40000 ALTER TABLE `tdap_historico` DISABLE KEYS */;
INSERT INTO `tdap_historico` (`id`, `dt_historico`, `setor`, `model`, `model_id`, `texto`, `arquivo`, `municipio`, `nao_aplica`, `created_at`, `updated_at`) VALUES
	(4, '2026-05-12 11:54:00', 'Fornecedor', '', '', 'teste de lancamento de histórico..', '', 'PEDRA BONITA', 'nao', '2026-05-12 14:55:11', '2026-05-12 14:55:11');
INSERT INTO `tdap_historico` (`id`, `dt_historico`, `setor`, `model`, `model_id`, `texto`, `arquivo`, `municipio`, `nao_aplica`, `created_at`, `updated_at`) VALUES
	(5, '2026-05-11 11:55:00', 'Ata', '', '', 'teste -  lancamento de ata', '', 'PEDRA BONITA', 'nao', '2026-05-12 14:55:55', '2026-05-12 14:55:55');
INSERT INTO `tdap_historico` (`id`, `dt_historico`, `setor`, `model`, `model_id`, `texto`, `arquivo`, `municipio`, `nao_aplica`, `created_at`, `updated_at`) VALUES
	(6, '2026-05-13 07:39:00', 'Fornecedor', '', '', 'Teste', '', 'PEDRA BONITA', 'nao', '2026-05-13 10:40:29', '2026-05-13 10:40:29');
/*!40000 ALTER TABLE `tdap_historico` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
