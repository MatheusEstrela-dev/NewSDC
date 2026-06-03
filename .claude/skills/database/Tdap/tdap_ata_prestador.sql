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

-- Copiando estrutura para tabela dbsdc.tdap_ata_prestador
CREATE TABLE IF NOT EXISTS `tdap_ata_prestador` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ata_id` int NOT NULL COMMENT 'Identificador Ata',
  `prestador_id` int NOT NULL COMMENT 'Identificador do Prestador',
  `status_prestador` tinyint NOT NULL DEFAULT '0' COMMENT 'Status Prestador',
  `dt_status_prestador` timestamp NOT NULL COMMENT 'Data desativação de Status',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tdap_ata_prestador_prestador_id_foreign` (`prestador_id`),
  KEY `tdap_ata_prestador_ata_id_foreign` (`ata_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_ata_prestador: 0 rows
/*!40000 ALTER TABLE `tdap_ata_prestador` DISABLE KEYS */;
/*!40000 ALTER TABLE `tdap_ata_prestador` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
