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

-- Copiando estrutura para tabela dbsdc.tdap_ata
CREATE TABLE IF NOT EXISTS `tdap_ata` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número Ata',
  `dt_inicio` timestamp NOT NULL COMMENT 'Data Inicial',
  `dt_final` timestamp NOT NULL COMMENT 'Data Final',
  `historico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Histórico',
  `planejamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sei` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_ata: 3 rows
/*!40000 ALTER TABLE `tdap_ata` DISABLE KEYS */;
INSERT INTO `tdap_ata` (`id`, `numero`, `dt_inicio`, `dt_final`, `historico`, `planejamento`, `sei`, `siad`, `created_at`, `updated_at`) VALUES
	(1, '125/2024', '2024-11-05 03:00:00', '2025-11-03 03:00:00', '* SERVIÇO DE TRANSPORTE E DISTRIBUIÇÃO DE ÁGUA POTÁVEL-TDAP', '256/2024', '1070.01.0000756/2024-42', '00002137-7', '2025-06-24 13:59:13', '2025-07-21 12:16:52');
INSERT INTO `tdap_ata` (`id`, `numero`, `dt_inicio`, `dt_final`, `historico`, `planejamento`, `sei`, `siad`, `created_at`, `updated_at`) VALUES
	(18, '99/2024', '2024-09-10 03:00:00', '2025-09-08 03:00:00', '* SERVIÇO DE TRANSPORTE DE ÁGUA POTÁVEL-TDAP', '97/2024', '1070.01.0000756/2024-42', '00002137-7', '2025-06-24 16:55:25', '2025-06-25 11:08:29');
INSERT INTO `tdap_ata` (`id`, `numero`, `dt_inicio`, `dt_final`, `historico`, `planejamento`, `sei`, `siad`, `created_at`, `updated_at`) VALUES
	(19, '4591', '2025-09-29 19:31:00', '2025-09-29 19:31:00', 'TESTE OBS', '0', '12121212', '15254855', '2025-09-29 19:31:25', '2025-09-29 19:31:25');
/*!40000 ALTER TABLE `tdap_ata` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
