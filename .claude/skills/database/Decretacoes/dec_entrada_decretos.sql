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

-- Copiando estrutura para tabela dbsdc.dec_entrada_decretos
CREATE TABLE IF NOT EXISTS `dec_entrada_decretos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entrada_processos_id` int unsigned NOT NULL,
  `decreto_categoria_id` int unsigned NOT NULL,
  `observacao` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.dec_entrada_decretos: ~19 rows (aproximadamente)
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(7, 1, 1, 'teste', '2025-07-18 13:14:34', '2025-07-18 13:14:34', '2025-07-18 13:14:34');
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(8, 1, 2, '', '2025-07-18 13:14:34', '2025-07-18 13:14:34', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(9, 2, 6, '', '2025-07-18 14:48:14', '2025-07-18 14:48:14', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(10, 2, 7, '', '2025-07-18 14:48:14', '2025-07-18 14:48:14', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(11, 2, 8, '', '2025-07-18 14:48:14', '2025-07-18 14:48:14', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(12, 2, 11, '', '2025-07-18 14:48:14', '2025-07-18 14:48:14', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(13, 3, 1, '', '2025-07-18 15:55:44', '2025-07-18 15:55:44', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(14, 3, 6, '', '2025-07-18 15:55:44', '2025-07-18 15:55:44', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(15, 3, 10, '', '2025-07-18 15:55:44', '2025-07-18 15:55:44', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(16, 3, 7, '', '2025-07-18 15:55:44', '2025-07-18 15:55:44', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(17, 3, 8, '', '2025-07-18 15:55:44', '2025-07-18 15:55:44', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(18, 4, 5, '', '2025-07-18 17:02:33', '2025-07-18 17:02:33', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(19, 4, 2, '', '2025-07-18 17:02:33', '2025-07-18 17:02:33', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(20, 4, 1, '', '2025-07-18 17:02:33', '2025-07-18 17:02:33', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(21, 4, 6, '', '2025-07-18 17:02:33', '2025-07-18 17:02:33', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(22, 5, 6, '', '2025-07-18 17:38:56', '2025-07-18 17:38:56', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(23, 5, 11, '', '2025-07-18 17:38:56', '2025-07-18 17:38:56', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(24, 5, 7, '', '2025-07-18 17:38:56', '2025-07-18 17:38:56', NULL);
INSERT INTO `dec_entrada_decretos` (`id`, `entrada_processos_id`, `decreto_categoria_id`, `observacao`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(25, 5, 8, '', '2025-07-18 17:38:56', '2025-07-18 17:38:56', '2025-07-18 13:14:34');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
