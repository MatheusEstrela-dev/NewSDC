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

-- Copiando estrutura para tabela dbsdc.dec_desastre_item_campos
CREATE TABLE IF NOT EXISTS `dec_desastre_item_campos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `desastre_item_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dec_desastre_item_campos_desastre_item_id_foreign` (`desastre_item_id`),
  CONSTRAINT `dec_desastre_item_campos_desastre_item_id_foreign` FOREIGN KEY (`desastre_item_id`) REFERENCES `dec_desastre_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.dec_desastre_item_campos: ~92 rows (aproximadamente)
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(1, 'number', 'Quantidade', 1, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(2, 'number', 'Quantidade', 2, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(3, 'number', 'Quantidade', 3, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(4, 'number', 'Quantidade', 4, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(5, 'number', 'Quantidade', 5, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(6, 'number', 'Quantidade', 6, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(7, 'number', 'Quantidade', 7, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(8, 'number', 'Quantidades danificadas', 8, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(9, 'number', 'Quantidades danificadas', 9, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(10, 'number', 'Quantidades danificadas', 10, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(11, 'number', 'Quantidades danificadas', 11, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(12, 'number', 'Quantidades danificadas', 12, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(13, 'number', 'Quantidades danificadas', 13, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(14, 'number', 'Quantidades destruídas', 8, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(15, 'number', 'Quantidades destruídas', 9, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(16, 'number', 'Quantidades destruídas', 10, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(17, 'number', 'Quantidades destruídas', 11, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(18, 'number', 'Quantidades destruídas', 12, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(19, 'number', 'Quantidades destruídas', 13, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(20, 'currency', 'Valor (R$)', 8, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(21, 'currency', 'Valor (R$)', 9, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(22, 'currency', 'Valor (R$)', 10, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(23, 'currency', 'Valor (R$)', 11, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(24, 'currency', 'Valor (R$)', 12, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(25, 'currency', 'Valor (R$)', 13, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(26, 'radio', 'Sim', 14, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(27, 'radio', 'Sim', 15, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(28, 'radio', 'Sim', 16, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(29, 'radio', 'Sim', 17, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(30, 'radio', 'Sim', 18, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(31, 'radio', 'Não', 14, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(32, 'radio', 'Não', 15, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(33, 'radio', 'Não', 16, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(34, 'radio', 'Não', 17, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(35, 'radio', 'Não', 18, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(36, 'select', 'População do município atingida', 14, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(37, 'select', 'População do município atingida', 15, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(38, 'select', 'População do município atingida', 16, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(39, 'select', 'População do município atingida', 17, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(40, 'select', 'Área atingida', 18, NULL, NULL);
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(119, 'radio', 'Não existe/Não afetada', 40, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(120, 'radio', 'Urbana', 40, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(121, 'radio', 'Rural', 40, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(122, 'radio', 'Urbana e rural', 40, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(123, 'radio', 'Não existe/Não afetada', 41, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(124, 'radio', 'Urbana', 41, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(125, 'radio', 'Rural', 41, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(126, 'radio', 'Urbana e rural', 41, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(127, 'radio', 'Não existe/Não afetada', 42, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(128, 'radio', 'Urbana', 42, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(129, 'radio', 'Rural', 42, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(130, 'radio', 'Urbana e rural', 42, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(131, 'radio', 'Não existe/Não afetada', 43, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(132, 'radio', 'Urbana', 43, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(133, 'radio', 'Rural', 43, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(134, 'radio', 'Urbana e rural', 43, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(135, 'radio', 'Não existe/Não afetada', 44, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(136, 'radio', 'Urbana', 44, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(137, 'radio', 'Rural', 44, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(138, 'radio', 'Urbana e rural', 44, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(139, 'radio', 'Não existe/Não afetada', 45, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(140, 'radio', 'Urbana', 45, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(141, 'radio', 'Rural', 45, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(142, 'radio', 'Urbana e rural', 45, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(143, 'radio', 'Não existe/Não afetada', 46, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(144, 'radio', 'Urbana', 46, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(145, 'radio', 'Rural', 46, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(146, 'radio', 'Urbana e rural', 46, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(147, 'radio', 'Não existe/Não afetada', 47, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(148, 'radio', 'Urbana', 47, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(149, 'radio', 'Rural', 47, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(150, 'radio', 'Urbana e rural', 47, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(151, 'radio', 'Não existe/Não afetada', 48, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(152, 'radio', 'Urbana', 48, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(153, 'radio', 'Rural', 48, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(154, 'radio', 'Urbana e rural', 48, '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(155, 'currency', 'Valor do prejuízo (R$)', 49, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(156, 'currency', 'Valor do prejuízo (R$)', 50, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(157, 'currency', 'Valor do prejuízo (R$)', 51, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(158, 'currency', 'Valor do prejuízo (R$)', 52, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(159, 'currency', 'Valor do prejuízo (R$)', 53, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(160, 'currency', 'Valor do prejuízo (R$)', 54, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(161, 'currency', 'Valor do prejuízo (R$)', 55, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(162, 'currency', 'Valor do prejuízo (R$)', 56, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(163, 'currency', 'Valor do prejuízo (R$)', 57, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(164, 'currency', 'Valor do prejuízo (R$)', 58, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(165, 'currency', 'Valor do prejuízo (R$)', 59, '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(166, 'currency', 'Valor do prejuízo (R$)', 60, '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(167, 'currency', 'Valor do prejuízo (R$)', 61, '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(168, 'currency', 'Valor do prejuízo (R$)', 62, '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(169, 'currency', 'Valor do prejuízo (R$)', 63, '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_item_campos` (`id`, `tipo`, `titulo`, `desastre_item_id`, `created_at`, `updated_at`) VALUES
	(170, 'currency', 'Valor do prejuízo (R$)', 64, '2025-09-03 20:25:55', '2025-09-03 20:25:55');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
