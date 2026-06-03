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

-- Copiando estrutura para tabela dbsdc.dec_decreto_categorias
CREATE TABLE IF NOT EXISTS `dec_decreto_categorias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.dec_decreto_categorias: ~11 rows (aproximadamente)
  
	(1, 'Desalojados', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(2, 'Desabrigados', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(3, 'Enfermos', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(4, 'Desaparecidos', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(5, 'Mortos', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(6, 'Outros afetados', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(7, 'Prejuízos econômicos Públicos', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(8, 'Prejuízos econômicos Privados', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(9, 'Agricultura pecuária', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(10, 'Danos Matérias', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');
  
	(11, 'Danos ambientais', NULL, '2025-07-18 12:36:41', '2025-07-18 12:36:41');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
