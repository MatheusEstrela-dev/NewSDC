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

-- Copiando estrutura para tabela dbsdc.tdap_crono_caminhoes
CREATE TABLE IF NOT EXISTS `tdap_crono_caminhoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cronograma_id` int unsigned NOT NULL,
  `caminhao_id` int unsigned NOT NULL,
  `comunidade_id` int unsigned NOT NULL,
  `agua_prevista` float unsigned NOT NULL DEFAULT (0),
  `num_viagens` smallint unsigned NOT NULL,
  `agua_entregue` float unsigned NOT NULL,
  `vr_total` double unsigned NOT NULL,
  `ordem` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `pmda_id` int unsigned DEFAULT NULL,
  `pip_pmda_comun_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5030 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_crono_caminhoes: 1.509 rows
/*!40000 ALTER TABLE `tdap_crono_caminhoes` DISABLE KEYS */;
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(211, 4, 20, 115, 119, 8, 119, 3440.2900390625, 8, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(210, 4, 20, 127, 43, 3, 43, 1243.1300048828125, 7, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(209, 4, 20, 125, 20, 1, 20, 578.2000122070312, 6, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(208, 4, 20, 119, 35, 2, 35, 1011.8499755859375, 5, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(58, 2, 20, 119, 35, 2, 5, 2, 8, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(57, 2, 20, 127, 43, 3, 4, 2, 7, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(56, 2, 20, 115, 119, 8, 1, 0, 6, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(55, 2, 20, 125, 20, 1, 9, 4, 5, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(54, 2, 20, 105, 106, 8, 1, 0, 4, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(53, 2, 20, 124, 34, 2, 5, 2, 3, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(52, 2, 20, 109, 27, 2, 7, 3, 2, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(51, 2, 20, 120, 6, 0, 31, 15, 1, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(50, 2, 20, 103, 188, 13, 1, 0, 0, '2025-07-09 17:23:57', '2025-07-09 17:23:57', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(76, 3, 21, 127, 43, 5, 1, 49, 2, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(75, 3, 21, 115, 119, 13, 0, 0, 1, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(74, 3, 21, 125, 20, 2, 4, 196, 0, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(73, 3, 20, 105, 106, 8, 1, 49, 0, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(72, 3, 19, 103, 188, 23, 0, 0, 2, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(71, 3, 19, 119, 35, 4, 1, 49, 1, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(70, 3, 19, 124, 34, 4, 2, 98, 0, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(69, 3, 18, 109, 27, 3, 4, 196, 1, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(68, 3, 18, 120, 6, 1, 18, 882, 0, '2025-07-11 12:16:21', '2025-07-11 12:16:21', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(207, 4, 20, 105, 106, 8, 106, 3064.4599609375, 4, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(206, 4, 20, 103, 188, 13, 188, 5435.080078125, 3, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(205, 4, 20, 124, 34, 2, 34, 982.9400024414062, 2, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(204, 4, 20, 109, 27, 2, 27, 780.5700073242188, 1, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(203, 4, 20, 120, 6, 1, 6, 173.4600067138672, 0, '2025-07-15 11:46:41', '2025-07-15 11:46:41', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(95, 5, 18, 120, 6, 1, 18, 520, 0, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(96, 5, 18, 124, 34, 3, 3, 87, 1, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(97, 5, 18, 115, 119, 11, 0, 0, 2, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(98, 5, 18, 125, 20, 2, 5, 145, 3, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(99, 5, 18, 127, 43, 4, 2, 58, 4, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(100, 5, 18, 119, 35, 3, 3, 87, 5, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(101, 5, 18, 105, 106, 10, 1, 29, 6, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(102, 5, 18, 103, 188, 17, 0, 0, 7, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(103, 5, 18, 109, 27, 3, 4, 116, 8, '2025-07-14 11:57:49', '2025-07-14 11:57:49', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(104, 6, 20, 105, 106, 8, 1, 29, 0, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(105, 6, 20, 120, 6, 0, 31, 896, 1, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(106, 6, 20, 109, 27, 2, 7, 202, 2, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(107, 6, 20, 124, 34, 2, 5, 145, 3, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(108, 6, 20, 103, 188, 13, 1, 29, 4, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(109, 6, 20, 115, 119, 8, 1, 29, 5, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(110, 6, 20, 127, 43, 3, 4, 116, 6, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(111, 6, 20, 125, 20, 1, 9, 260, 7, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(112, 6, 20, 119, 35, 2, 5, 145, 8, '2025-07-14 12:02:43', '2025-07-14 12:02:43', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(241, 9, 86, 1063, 52, 4, 52, 2435.679931640625, 3, '2025-07-23 18:54:28', '2025-07-23 18:54:28', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(240, 9, 86, 1065, 48, 3, 48, 2248.320068359375, 2, '2025-07-23 18:54:28', '2025-07-23 18:54:28', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(239, 9, 86, 3055, 72, 5, 72, 3372.47998046875, 1, '2025-07-23 18:54:28', '2025-07-23 18:54:28', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(238, 9, 86, 3045, 36, 2, 36, 1686.239990234375, 0, '2025-07-23 18:54:28', '2025-07-23 18:54:28', NULL, NULL, NULL);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(304, 8, 17, 2506, 91, 8, 91, 5460, 2, '2025-07-25 14:10:03', '2025-07-25 14:10:03', NULL, 1588, 12259);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(303, 8, 17, 2505, 628, 52, 628, 37680, 1, '2025-07-25 14:10:03', '2025-07-25 14:10:03', NULL, 1588, 12258);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(302, 8, 17, 2508, 24, 2, 24, 1440, 0, '2025-07-25 14:10:03', '2025-07-25 14:10:03', NULL, 1588, 12260);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3817, 7, 19, 124, 19.2, 2, 16.6, 479.906, 0, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10397);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(407, 10, 48, 3563, 20, 1, 19, 1140, 7, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11654);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(406, 10, 48, 3557, 52, 3, 57, 3420, 6, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11652);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(405, 10, 48, 3588, 16, 1, 19, 1140, 5, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11648);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(404, 10, 48, 3566, 17, 1, 19, 1140, 4, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11655);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(403, 10, 48, 3556, 16, 1, 19, 1140, 3, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11653);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(402, 10, 48, 3575, 41, 2, 38, 2280, 2, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11651);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(401, 10, 48, 3562, 34, 2, 38, 2280, 1, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11650);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(400, 10, 48, 3551, 31, 2, 38, 2280, 0, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11649);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(399, 10, 45, 3552, 25, 2, 27, 1620, 4, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11647);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(398, 10, 45, 3571, 22, 2, 27, 1620, 3, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11645);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(397, 10, 45, 3567, 15, 1, 14, 810, 2, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11646);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(396, 10, 45, 3553, 19, 1, 14, 810, 1, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11644);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(395, 10, 45, 3576, 28, 2, 27, 1620, 0, '2025-07-29 15:03:52', '2025-07-29 15:03:52', NULL, 1526, 11643);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1014, 11, 56, 3313, 19, 1, 30, 1137.9000244140625, 6, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12142);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1013, 11, 56, 4365, 193, 6, 180, 6827.39990234375, 5, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12652);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1012, 11, 56, 3311, 73, 2, 60, 2275.800048828125, 4, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12153);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1011, 11, 56, 3314, 247, 8, 240, 9103.2001953125, 3, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12654);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1010, 11, 56, 4370, 422, 14, 420, 15930.599609375, 2, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12656);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1009, 11, 56, 3295, 27, 1, 30, 1137.9000244140625, 1, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12138);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1008, 11, 56, 4371, 97, 3, 90, 3413.699951171875, 0, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12657);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1007, 11, 54, 3316, 61, 4, 54, 2048.219970703125, 0, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12144);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1006, 11, 48, 3305, 121, 6, 114, 4324.02001953125, 10, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12155);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1005, 11, 48, 3413, 60, 3, 57, 2162.010009765625, 9, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12156);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1004, 11, 48, 3310, 86, 5, 95, 3603.35009765625, 8, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12139);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1003, 11, 48, 3294, 331, 17, 323, 12251.3896484375, 7, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12148);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1002, 11, 48, 4372, 305, 16, 304, 11530.7197265625, 6, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12658);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1001, 11, 48, 3317, 40, 2, 38, 1441.3399658203125, 5, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12141);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1000, 11, 48, 4363, 158, 8, 152, 5765.35986328125, 4, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12650);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(999, 11, 48, 3315, 48, 3, 57, 2162.010009765625, 3, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12147);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(998, 11, 48, 4374, 74, 4, 76, 2882.679931640625, 2, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12659);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(997, 11, 48, 4364, 388, 20, 380, 14413.400390625, 1, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12651);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(996, 11, 48, 3312, 26, 1, 19, 720.6699829101562, 0, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12152);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(995, 11, 47, 3296, 36, 1, 27, 1024.1099853515625, 8, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12145);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(994, 11, 47, 3297, 178, 7, 186, 7054.97998046875, 7, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12151);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(993, 11, 47, 3298, 170, 6, 159, 6030.8701171875, 6, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12143);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(992, 11, 47, 3299, 63, 2, 53, 2010.2900390625, 5, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12154);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(991, 11, 47, 3300, 21, 1, 27, 1024.1099853515625, 4, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12146);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(990, 11, 47, 3302, 189, 7, 186, 7054.97998046875, 3, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12140);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(989, 11, 47, 3410, 108, 4, 106, 4020.580078125, 2, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12150);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(988, 11, 47, 3303, 52, 2, 53, 2010.2900390625, 1, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12655);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(987, 11, 47, 3409, 60, 2, 53, 2010.2900390625, 0, '2025-08-13 18:48:00', '2025-08-13 18:48:00', NULL, 1577, 12149);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(346, 12, 48, 2487, 22, 1, 22, 1320, 0, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12078);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(347, 12, 48, 2490, 15, 1, 15, 900, 1, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12072);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(348, 12, 48, 2492, 30, 2, 30, 1800, 2, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12073);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(349, 12, 48, 2494, 24, 1, 24, 1440, 3, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12071);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(350, 12, 48, 2544, 22, 1, 22, 1320, 4, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12074);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(351, 12, 48, 2493, 14, 1, 14, 840, 5, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12075);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(352, 12, 48, 2488, 17, 1, 17, 1020, 6, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12079);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(353, 12, 48, 2491, 23, 1, 23, 1380, 7, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12077);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(354, 12, 48, 2495, 38, 2, 38, 2280, 8, '2025-07-28 12:53:27', '2025-07-28 12:53:27', NULL, 1569, 12076);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(355, 13, 48, 2487, 22, 1, 22, 1320, 0, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12078);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(356, 13, 48, 2490, 15, 1, 15, 900, 1, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12072);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(357, 13, 48, 2492, 30, 2, 30, 1800, 2, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12073);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(358, 13, 48, 2494, 24, 1, 24, 1440, 3, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12071);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(359, 13, 48, 2544, 22, 1, 22, 1320, 4, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12074);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(360, 13, 48, 2493, 14, 1, 14, 840, 5, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12075);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(361, 13, 48, 2488, 17, 1, 17, 1020, 6, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12079);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(362, 13, 48, 2491, 23, 1, 23, 1380, 7, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12077);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(363, 13, 48, 2495, 38, 2, 38, 2280, 8, '2025-07-28 12:56:50', '2025-07-28 12:56:50', NULL, 1569, 12076);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4338, 14, 113, 2493, 13.8, 2, 16, 960, 0, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12075);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4337, 14, 113, 2491, 22.8, 3, 24, 1440, 1, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12077);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4336, 14, 113, 2544, 21.6, 3, 24, 1440, 2, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12074);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4335, 14, 113, 2488, 17.4, 2, 16, 960, 3, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12079);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4334, 14, 113, 2495, 38.4, 5, 40, 2400, 4, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12076);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4333, 14, 113, 2494, 24, 3, 24, 1440, 5, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12071);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4332, 14, 113, 2487, 22.2, 3, 24, 1440, 6, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12078);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4331, 14, 113, 2492, 30, 4, 32, 1920, 7, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12073);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4330, 14, 113, 2490, 15, 2, 16, 960, 8, '2025-10-02 17:12:59', '2025-10-29 12:05:32', NULL, 1569, 12072);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2886, 15, 98, 627, 24, 2, 22, 1320, 6, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11839);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2885, 15, 98, 4004, 6, 1, 11, 660, 5, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11838);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2884, 15, 98, 628, 24, 2, 22, 1320, 4, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11837);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2883, 15, 98, 4003, 36, 3, 33, 1980, 3, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11836);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2882, 15, 98, 636, 24, 2, 22, 1320, 2, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11835);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2881, 15, 98, 4001, 21, 2, 22, 1320, 1, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11834);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2880, 15, 98, 630, 18, 2, 22, 1320, 0, '2025-09-11 13:49:53', '2025-09-11 13:49:53', NULL, 1542, 11958);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1358, 16, 17, 2503, 99, 8, 96, 5760, 0, '2025-08-14 18:30:55', '2025-10-16 13:17:52', '2025-10-16 13:17:52', 1570, 12080);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1357, 16, 17, 2504, 64, 5, 60, 3600, 1, '2025-08-14 18:30:55', '2025-10-16 13:17:52', '2025-10-16 13:17:52', 1570, 12083);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1356, 16, 17, 3981, 74, 6, 72, 4320, 2, '2025-08-14 18:30:55', '2025-10-16 13:17:52', '2025-10-16 13:17:52', 1570, 12084);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1355, 16, 17, 2489, 52, 4, 48, 2880, 3, '2025-08-14 18:30:55', '2025-10-16 13:17:52', '2025-10-16 13:17:52', 1570, 12082);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1354, 16, 17, 2512, 59, 5, 60, 3600, 4, '2025-08-14 18:30:55', '2025-10-16 13:17:52', '2025-10-16 13:17:52', 1570, 12081);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3981, 17, 50, 384, 15.6, 2, 20, 1200, 0, '2025-09-26 12:01:36', '2025-10-28 12:41:23', NULL, 1606, 12400);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3980, 17, 50, 386, 31.2, 3, 30, 1800, 1, '2025-09-26 12:01:36', '2025-10-28 12:41:23', NULL, 1606, 12402);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3979, 17, 50, 382, 43.2, 4, 40, 2400, 2, '2025-09-26 12:01:36', '2025-10-28 12:41:23', NULL, 1606, 12401);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4254, 18, 29, 2091, 23, 2, 20, 1040, 5, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12394);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4253, 18, 29, 3970, 57, 6, 60, 3120, 4, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12385);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4252, 18, 29, 2085, 51, 5, 50, 2600, 3, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12384);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4251, 18, 29, 3962, 56, 6, 60, 3120, 2, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12383);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4250, 18, 29, 3853, 82, 8, 80, 4160, 1, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12381);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4249, 18, 29, 2080, 63, 6, 60, 3120, 0, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12382);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4248, 18, 28, 2081, 73, 8, 72.8, 3785.6, 6, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12380);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4247, 18, 28, 2087, 120, 13, 118.3, 6151.6, 5, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12387);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4246, 18, 28, 2089, 54, 6, 54.6, 2839.2, 4, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12386);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4245, 18, 28, 3887, 47, 5, 45.5, 2366, 3, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12388);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4244, 18, 28, 2084, 58, 6, 54.6, 2839.2, 2, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12379);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4243, 18, 28, 2082, 72, 8, 72.8, 3785.6, 1, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12378);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4242, 18, 28, 2083, 67, 7, 63.7, 3312.4, 0, '2025-09-30 18:03:11', '2025-09-30 18:03:11', NULL, 1600, 12377);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4700, 19, 62, 4372, 305, 10, 300, 11379, 0, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12658);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4699, 19, 62, 4371, 97, 3, 90, 3413.7, 1, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12657);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4698, 19, 62, 4370, 422, 14, 420, 15930.6, 2, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12656);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4697, 19, 62, 3303, 52, 2, 60, 2275.8, 3, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12655);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4696, 19, 62, 3314, 247, 8, 240, 9103.2, 4, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12654);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4695, 19, 62, 4374, 74, 2, 60, 2275.8, 5, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12659);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4694, 19, 61, 4364, 388, 13, 390, 14792.7, 0, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12651);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4693, 19, 61, 3413, 60, 2, 60, 2275.8, 1, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12156);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4692, 19, 61, 4363, 158, 5, 150, 5689.5, 2, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12650);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4691, 19, 61, 3299, 63, 2, 60, 2275.8, 3, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12154);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4690, 19, 61, 3305, 121, 4, 120, 4551.6, 4, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12155);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4689, 19, 61, 3312, 26, 1, 30, 1137.9, 5, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12152);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4688, 19, 61, 3297, 178, 6, 180, 6827.4, 6, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12151);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4687, 19, 61, 3311, 73, 2, 60, 2275.8, 7, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12153);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4686, 19, 61, 4365, 193, 6, 180, 6827.4, 8, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12652);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4685, 19, 60, 3409, 60, 2, 60, 2275.8, 0, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12149);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4684, 19, 60, 3410, 108, 4, 120, 4551.6, 1, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12150);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4683, 19, 60, 3295, 27, 1, 30, 1137.9, 2, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12138);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4682, 19, 60, 3310, 86, 3, 90, 3413.7, 3, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12139);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4681, 19, 60, 3315, 48, 2, 60, 2275.8, 4, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12147);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4680, 19, 60, 3300, 21, 1, 30, 1137.9, 5, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12146);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4679, 19, 60, 3294, 331, 11, 330, 12516.9, 6, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12148);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4678, 19, 60, 3298, 170, 6, 180, 6827.4, 7, '2025-10-09 12:03:54', '2025-10-10 17:20:36', NULL, 1577, 12143);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4677, 19, 60, 3317, 40, 1, 30, 1137.9, 8, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12141);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4676, 19, 60, 3313, 19, 1, 30, 1137.9, 9, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12142);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4675, 19, 60, 3316, 61, 2, 60, 2275.8, 10, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12144);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4674, 19, 60, 3296, 36, 1, 30, 1137.9, 11, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12145);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4673, 19, 60, 3302, 189, 6, 180, 6827.4, 12, '2025-10-09 12:03:54', '2025-10-10 17:20:37', NULL, 1577, 12140);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2464, 20, 27, 2029, 36, 3, 34.5, 1794, 10, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12483);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2463, 20, 27, 2038, 24, 2, 23, 1196, 9, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12484);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2462, 20, 27, 4349, 24, 2, 23, 1196, 8, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12503);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2461, 20, 27, 4350, 24, 2, 23, 1196, 7, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12502);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2460, 20, 27, 2037, 24, 2, 23, 1196, 6, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12501);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2459, 20, 27, 2222, 60, 5, 57.5, 2990, 5, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12486);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2458, 20, 27, 2024, 45, 4, 46, 2392, 4, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12494);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2457, 20, 27, 2014, 54, 5, 57.5, 2990, 3, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12495);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2456, 20, 27, 2021, 54, 5, 57.5, 2990, 2, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12489);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2455, 20, 27, 2011, 120, 10, 115, 5980, 1, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12488);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2454, 20, 27, 2023, 60, 5, 57.5, 2990, 0, '2025-09-08 18:31:41', '2025-09-08 18:31:41', NULL, 1614, 12485);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2481, 21, 32, 2040, 162, 12, 158.4, 8236.8, 16, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12580);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2480, 21, 32, 2306, 46, 4, 52.8, 2745.6, 15, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12579);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2479, 21, 32, 2045, 47, 4, 52.8, 2745.6, 14, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12576);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2478, 21, 32, 2055, 28, 2, 26.4, 1372.8, 13, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12575);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2477, 21, 32, 2059, 73, 6, 79.2, 4118.4, 12, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12574);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2476, 21, 32, 2048, 49, 4, 52.8, 2745.6, 11, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12572);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2475, 21, 32, 2043, 23, 2, 26.4, 1372.8, 10, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12578);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2474, 21, 32, 2046, 60, 5, 66, 3432, 9, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12567);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2473, 21, 32, 2060, 47, 4, 52.8, 2745.6, 8, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12556);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2472, 21, 32, 2307, 27, 2, 26.4, 1372.8, 7, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12552);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2471, 21, 32, 2053, 114, 9, 118.8, 6177.6, 6, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12568);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2470, 21, 32, 2042, 34, 3, 39.6, 2059.2, 5, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12550);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2469, 21, 32, 2305, 47, 4, 52.8, 2745.6, 4, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12551);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2468, 21, 32, 2050, 33, 3, 39.6, 2059.2, 3, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12547);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2467, 21, 32, 2062, 34, 3, 39.6, 2059.2, 2, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12524);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2466, 21, 32, 2054, 30, 2, 26.4, 1372.8, 1, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12545);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2465, 21, 32, 2041, 70, 5, 66, 3432, 0, '2025-09-08 18:32:23', '2025-09-08 18:32:23', NULL, 1617, 12577);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4307, 22, 99, 3566, 16.8, 2, 22, 1320, 0, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11655);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4306, 22, 99, 3576, 28.2, 3, 33, 1980, 1, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11643);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4305, 22, 99, 3553, 18.6, 2, 22, 1320, 2, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11644);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4304, 22, 99, 3571, 21.6, 2, 22, 1320, 3, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11645);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4303, 22, 99, 3567, 15, 1, 11, 660, 4, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11646);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4302, 22, 99, 3552, 25.2, 2, 22, 1320, 5, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11647);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4301, 22, 99, 3551, 31.2, 3, 33, 1980, 6, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11649);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4300, 22, 99, 3588, 16.2, 1, 11, 660, 7, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11648);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4299, 22, 99, 3563, 19.8, 2, 22, 1320, 8, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11654);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4298, 22, 99, 3562, 34.2, 3, 33, 1980, 9, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11650);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4297, 22, 99, 3575, 40.8, 4, 44, 2640, 10, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11651);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4296, 22, 99, 3557, 52.2, 5, 55, 3300, 11, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11652);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4295, 22, 99, 3556, 16.2, 1, 11, 660, 12, '2025-10-01 14:36:02', '2025-10-22 19:27:11', NULL, 1526, 11653);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3366, 23, 105, 4281, 150, 13, 156, 8112, 0, '2025-09-16 13:00:03', '2025-10-29 12:30:20', NULL, 1592, 12355);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3365, 23, 105, 4279, 204, 17, 204, 10608, 1, '2025-09-16 13:00:03', '2025-10-29 12:30:20', NULL, 1592, 12352);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3364, 23, 105, 3523, 168, 14, 168, 8736, 2, '2025-09-16 13:00:03', '2025-10-29 12:30:20', NULL, 1592, 12281);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3363, 23, 105, 4280, 138, 12, 144, 7488, 3, '2025-09-16 13:00:03', '2025-10-29 12:30:20', NULL, 1592, 12365);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1346, 24, 101, 2506, 91, 8, 96, 5760, 2, '2025-08-14 18:27:29', '2025-08-14 18:27:29', NULL, 1588, 12259);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1345, 24, 101, 2508, 24, 2, 24, 1440, 1, '2025-08-14 18:27:29', '2025-08-14 18:27:29', NULL, 1588, 12260);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1344, 24, 101, 2505, 628, 52, 624, 37440, 0, '2025-08-14 18:27:29', '2025-08-14 18:27:29', NULL, 1588, 12258);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4146, 25, 59, 2512, 58.8, 6, 60, 3600, 0, '2025-09-29 18:47:15', '2025-10-29 12:08:08', NULL, 1570, 12081);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4145, 25, 59, 2489, 52.2, 5, 50, 3000, 1, '2025-09-29 18:47:15', '2025-10-29 12:08:08', NULL, 1570, 12082);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4144, 25, 59, 3981, 74.4, 7, 70, 4200, 2, '2025-09-29 18:47:15', '2025-10-29 12:08:08', NULL, 1570, 12084);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4143, 25, 59, 2503, 99, 10, 100, 6000, 3, '2025-09-29 18:47:15', '2025-10-29 12:08:08', NULL, 1570, 12080);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4142, 25, 59, 2504, 63.6, 6, 60, 3600, 4, '2025-09-29 18:47:15', '2025-10-29 12:08:08', NULL, 1570, 12083);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(767, 28, 97, 3044, 116, 58, 116, 3944, 0, '2025-08-07 11:53:19', '2025-08-07 11:53:19', NULL, 1521, 11639);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(768, 28, 97, 3042, 97, 48, 96, 3264, 1, '2025-08-07 11:53:19', '2025-08-07 11:53:19', NULL, 1521, 11642);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(769, 28, 97, 3047, 118, 59, 118, 4012, 2, '2025-08-07 11:53:19', '2025-08-07 11:53:19', NULL, 1521, 11656);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(770, 28, 97, 3049, 35, 18, 36, 1224, 3, '2025-08-07 11:53:19', '2025-08-07 11:53:19', NULL, 1521, 11657);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(771, 28, 97, 3041, 64, 32, 64, 2176, 4, '2025-08-07 11:53:19', '2025-08-07 11:53:19', NULL, 1521, 11658);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(772, 28, 97, 3547, 80, 40, 80, 2720, 5, '2025-08-07 11:53:19', '2025-08-07 11:53:19', NULL, 1521, 12280);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(773, 30, 25, 1444, 28, 4, 28, 1311.52001953125, 0, '2025-08-07 11:59:02', '2025-08-07 11:59:02', NULL, 1622, 12562);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(774, 30, 26, 1449, 11, 1, 8, 374.7200012207031, 0, '2025-08-07 11:59:02', '2025-08-07 11:59:02', NULL, 1622, 12563);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(775, 30, 86, 2473, 170, 12, 174, 8150.16015625, 0, '2025-08-07 11:59:02', '2025-08-07 11:59:02', NULL, 1622, 12565);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(776, 30, 87, 1441, 28, 4, 32, 1498.8800048828125, 0, '2025-08-07 11:59:02', '2025-08-07 11:59:02', NULL, 1622, 12573);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(777, 33, 97, 688, 419, 210, 420, 15120, 0, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11972);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(778, 33, 97, 684, 188, 94, 188, 6768, 1, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11973);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(779, 33, 97, 674, 595, 298, 596, 21456, 2, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11974);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(780, 33, 97, 662, 243, 122, 244, 8784, 3, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11975);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(781, 33, 97, 670, 1756, 878, 1756, 63216, 4, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11976);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(782, 33, 97, 665, 863, 431, 862, 31032, 5, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11977);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(783, 33, 97, 677, 480, 240, 480, 17280, 6, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11978);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(784, 33, 97, 660, 231, 115, 230, 8280, 7, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11979);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(785, 33, 97, 673, 1130, 565, 1130, 40680, 8, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11980);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(786, 33, 97, 686, 541, 270, 540, 19440, 9, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11981);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(787, 33, 97, 659, 1822, 911, 1822, 65592, 10, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11982);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(788, 33, 97, 3918, 1950, 975, 1950, 70200, 11, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11983);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(789, 33, 97, 678, 425, 213, 426, 15336, 12, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11984);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(790, 33, 97, 671, 407, 204, 408, 14688, 13, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11985);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(791, 33, 97, 664, 358, 179, 358, 12888, 14, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11986);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(792, 33, 97, 679, 243, 122, 244, 8784, 15, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11987);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(793, 33, 97, 683, 553, 276, 552, 19872, 16, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11988);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(794, 33, 97, 661, 589, 295, 590, 21240, 17, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11989);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(795, 33, 97, 685, 535, 267, 534, 19224, 18, '2025-08-07 13:12:41', '2025-08-07 13:12:41', NULL, 1561, 11990);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(796, 34, 97, 688, 419, 210, 420, 15120, 0, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11972);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(797, 34, 97, 684, 188, 94, 188, 6768, 1, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11973);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(798, 34, 97, 674, 595, 298, 596, 21456, 2, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11974);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(799, 34, 97, 662, 243, 122, 244, 8784, 3, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11975);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(800, 34, 97, 670, 1756, 878, 1756, 63216, 4, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11976);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(801, 34, 97, 665, 863, 431, 862, 31032, 5, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11977);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(802, 34, 97, 677, 480, 240, 480, 17280, 6, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11978);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(803, 34, 97, 660, 231, 115, 230, 8280, 7, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11979);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(804, 34, 97, 673, 1130, 565, 1130, 40680, 8, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11980);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(805, 34, 97, 686, 541, 270, 540, 19440, 9, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11981);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(806, 34, 97, 659, 1822, 911, 1822, 65592, 10, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11982);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(807, 34, 97, 3918, 1950, 975, 1950, 70200, 11, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11983);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(808, 34, 97, 678, 425, 213, 426, 15336, 12, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11984);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(809, 34, 97, 671, 407, 204, 408, 14688, 13, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11985);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(810, 34, 97, 664, 358, 179, 358, 12888, 14, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11986);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(811, 34, 97, 679, 243, 122, 244, 8784, 15, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11987);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(812, 34, 97, 683, 553, 276, 552, 19872, 16, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11988);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(813, 34, 97, 661, 589, 295, 590, 21240, 17, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11989);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(814, 34, 97, 685, 535, 267, 534, 19224, 18, '2025-08-07 13:13:25', '2025-08-07 13:13:25', NULL, 1561, 11990);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(815, 35, 97, 688, 419, 210, 420, 15120, 0, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11972);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(816, 35, 97, 684, 188, 94, 188, 6768, 1, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11973);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(817, 35, 97, 674, 595, 298, 596, 21456, 2, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11974);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(818, 35, 97, 662, 243, 122, 244, 8784, 3, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11975);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(819, 35, 97, 670, 1756, 878, 1756, 63216, 4, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11976);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(820, 35, 97, 665, 863, 431, 862, 31032, 5, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11977);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(821, 35, 97, 677, 480, 240, 480, 17280, 6, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11978);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(822, 35, 97, 660, 231, 115, 230, 8280, 7, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11979);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(823, 35, 97, 673, 1130, 565, 1130, 40680, 8, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11980);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(824, 35, 97, 686, 541, 270, 540, 19440, 9, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11981);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(825, 35, 97, 659, 1822, 911, 1822, 65592, 10, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11982);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(826, 35, 97, 3918, 1950, 975, 1950, 70200, 11, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11983);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(827, 35, 97, 678, 425, 213, 426, 15336, 12, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11984);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(828, 35, 97, 671, 407, 204, 408, 14688, 13, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11985);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(829, 35, 97, 664, 358, 179, 358, 12888, 14, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11986);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(830, 35, 97, 679, 243, 122, 244, 8784, 15, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11987);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(831, 35, 97, 683, 553, 276, 552, 19872, 16, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11988);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(832, 35, 97, 661, 589, 295, 590, 21240, 17, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11989);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(833, 35, 97, 685, 535, 267, 534, 19224, 18, '2025-08-07 13:13:50', '2025-08-07 13:13:50', NULL, 1561, 11990);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(834, 36, 97, 688, 419, 210, 420, 15120, 0, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11972);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(835, 36, 97, 684, 188, 94, 188, 6768, 1, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11973);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(836, 36, 97, 674, 595, 298, 596, 21456, 2, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11974);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(837, 36, 97, 662, 243, 122, 244, 8784, 3, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11975);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(838, 36, 97, 670, 1756, 878, 1756, 63216, 4, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11976);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(839, 36, 97, 665, 863, 431, 862, 31032, 5, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11977);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(840, 36, 97, 677, 480, 240, 480, 17280, 6, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11978);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(841, 36, 97, 660, 231, 115, 230, 8280, 7, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11979);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(842, 36, 97, 673, 1130, 565, 1130, 40680, 8, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11980);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(843, 36, 97, 686, 541, 270, 540, 19440, 9, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11981);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(844, 36, 97, 659, 1822, 911, 1822, 65592, 10, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11982);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(845, 36, 97, 3918, 1950, 975, 1950, 70200, 11, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11983);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(846, 36, 97, 678, 425, 213, 426, 15336, 12, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11984);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(847, 36, 97, 671, 407, 204, 408, 14688, 13, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11985);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(848, 36, 97, 664, 358, 179, 358, 12888, 14, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11986);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(849, 36, 97, 679, 243, 122, 244, 8784, 15, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11987);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(850, 36, 97, 683, 553, 276, 552, 19872, 16, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11988);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(851, 36, 97, 661, 589, 295, 590, 21240, 17, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11989);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(852, 36, 97, 685, 535, 267, 534, 19224, 18, '2025-08-07 13:16:41', '2025-08-07 13:16:41', NULL, 1561, 11990);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(853, 37, 97, 688, 419, 210, 420, 15120, 0, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11972);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(854, 37, 97, 684, 188, 94, 188, 6768, 1, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11973);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(855, 37, 97, 674, 595, 298, 596, 21456, 2, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11974);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(856, 37, 97, 662, 243, 122, 244, 8784, 3, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11975);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(857, 37, 97, 670, 1756, 878, 1756, 63216, 4, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11976);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(858, 37, 97, 665, 863, 431, 862, 31032, 5, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11977);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(859, 37, 97, 677, 480, 240, 480, 17280, 6, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11978);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(860, 37, 97, 660, 231, 115, 230, 8280, 7, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11979);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(861, 37, 97, 673, 1130, 565, 1130, 40680, 8, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11980);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(862, 37, 97, 686, 541, 270, 540, 19440, 9, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11981);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(863, 37, 97, 659, 1822, 911, 1822, 65592, 10, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11982);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(864, 37, 97, 3918, 1950, 975, 1950, 70200, 11, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11983);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(865, 37, 97, 678, 425, 213, 426, 15336, 12, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11984);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(866, 37, 97, 671, 407, 204, 408, 14688, 13, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11985);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(867, 37, 97, 664, 358, 179, 358, 12888, 14, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11986);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(868, 37, 97, 679, 243, 122, 244, 8784, 15, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11987);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(869, 37, 97, 683, 553, 276, 552, 19872, 16, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11988);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(870, 37, 97, 661, 589, 295, 590, 21240, 17, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11989);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(871, 37, 97, 685, 535, 267, 534, 19224, 18, '2025-08-07 13:18:36', '2025-08-07 13:18:36', NULL, 1561, 11990);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(872, 38, 97, 1703, 122, 61, 122, 4392, 0, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12665);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(873, 38, 97, 2381, 19, 9, 18, 648, 1, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12666);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(874, 38, 97, 2379, 35, 18, 36, 1296, 2, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12667);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(875, 38, 97, 1702, 40, 20, 40, 1440, 3, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12668);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(876, 38, 97, 1707, 12, 6, 12, 432, 4, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12669);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(877, 38, 97, 2375, 23, 12, 24, 864, 5, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12670);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(878, 38, 97, 1705, 70, 35, 70, 2520, 6, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12671);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(879, 38, 97, 1706, 29, 14, 28, 1008, 7, '2025-08-07 13:25:46', '2025-08-07 13:25:46', NULL, 1629, 12672);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(880, 39, 97, 1703, 122, 61, 122, 4392, 0, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12665);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(881, 39, 97, 2381, 19, 9, 18, 648, 1, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12666);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(882, 39, 97, 2379, 35, 18, 36, 1296, 2, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12667);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(883, 39, 97, 1702, 40, 20, 40, 1440, 3, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12668);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(884, 39, 97, 1707, 12, 6, 12, 432, 4, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12669);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(885, 39, 97, 2375, 23, 12, 24, 864, 5, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12670);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(886, 39, 97, 1705, 70, 35, 70, 2520, 6, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12671);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(887, 39, 97, 1706, 29, 14, 28, 1008, 7, '2025-08-07 13:33:25', '2025-08-07 13:33:25', NULL, 1629, 12672);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(926, 41, 108, 251, 22, 2, 20, 910.5999755859375, 3, '2025-08-12 12:46:17', '2025-08-12 12:46:17', NULL, 1573, 12113);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(925, 41, 108, 262, 73, 7, 71, 3232.6298828125, 2, '2025-08-12 12:46:17', '2025-08-12 12:46:17', NULL, 1573, 12112);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(924, 41, 108, 247, 58, 6, 61, 2777.330078125, 1, '2025-08-12 12:46:17', '2025-08-12 12:46:17', NULL, 1573, 12122);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(923, 41, 108, 3703, 58, 6, 61, 2777.330078125, 0, '2025-08-12 12:46:17', '2025-08-12 12:46:17', NULL, 1573, 12114);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(893, 42, 108, 3703, 58, 6, 61, 2777.330078125, 0, '2025-08-11 13:48:58', '2025-08-11 13:48:58', NULL, 1573, 12114);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(894, 42, 108, 247, 58, 6, 61, 2777.330078125, 1, '2025-08-11 13:48:58', '2025-08-11 13:48:58', NULL, 1573, 12122);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(895, 42, 108, 262, 73, 7, 71, 3232.6298828125, 2, '2025-08-11 13:48:58', '2025-08-11 13:48:58', NULL, 1573, 12112);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(896, 42, 108, 251, 22, 2, 20, 910.5999755859375, 3, '2025-08-11 13:48:58', '2025-08-11 13:48:58', NULL, 1573, 12113);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2559, 43, 57, 597, 101, 8, 96, 4992, 12, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11520);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2558, 43, 57, 595, 8, 1, 12, 624, 11, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11519);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2557, 43, 57, 1298, 53, 4, 48, 2496, 10, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11518);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2556, 43, 57, 1300, 11, 1, 12, 624, 9, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11517);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2555, 43, 57, 1302, 22, 2, 24, 1248, 8, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11516);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2554, 43, 57, 587, 54, 5, 60, 3120, 7, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11515);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2553, 43, 57, 585, 71, 6, 72, 3744, 6, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11514);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2552, 43, 57, 596, 89, 7, 84, 4368, 5, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11513);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2551, 43, 57, 590, 10, 1, 12, 624, 4, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11512);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2550, 43, 57, 588, 118, 10, 120, 6240, 3, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11510);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2549, 43, 57, 586, 27, 2, 24, 1248, 2, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11509);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2548, 43, 57, 589, 23, 2, 24, 1248, 1, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11511);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2547, 43, 57, 591, 26, 2, 24, 1248, 0, '2025-09-08 18:46:19', '2025-09-08 18:46:19', NULL, 1317, 11521);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2533, 44, 20, 874, 53, 4, 56, 2744, 21, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12097);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2532, 44, 20, 882, 49, 4, 56, 2744, 20, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12089);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2531, 44, 20, 895, 39, 3, 42, 2058, 19, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12092);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2530, 44, 20, 894, 61, 4, 56, 2744, 18, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12090);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2529, 44, 20, 877, 54, 4, 56, 2744, 17, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12091);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2528, 44, 20, 868, 22, 2, 28, 1372, 16, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12095);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2527, 44, 20, 897, 114, 8, 112, 5488, 15, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12093);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2526, 44, 20, 884, 78, 6, 84, 4116, 14, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12094);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2525, 44, 20, 872, 21, 2, 28, 1372, 13, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12096);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2524, 44, 20, 878, 58, 4, 56, 2744, 12, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12098);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2523, 44, 20, 876, 56, 4, 56, 2744, 11, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12110);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2522, 44, 20, 889, 23, 2, 28, 1372, 10, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12107);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2521, 44, 20, 3706, 59, 4, 56, 2744, 9, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12109);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2520, 44, 20, 3705, 39, 3, 42, 2058, 8, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12108);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2519, 44, 20, 883, 69, 5, 70, 3430, 7, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12105);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2518, 44, 20, 869, 27, 2, 28, 1372, 6, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12106);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2517, 44, 20, 867, 117, 8, 112, 5488, 5, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12103);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2516, 44, 20, 870, 27, 2, 28, 1372, 4, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12104);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2515, 44, 20, 888, 73, 5, 70, 3430, 3, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12102);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2514, 44, 20, 893, 52, 4, 56, 2744, 2, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12099);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2513, 44, 20, 892, 24, 2, 28, 1372, 1, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12101);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2512, 44, 20, 880, 28, 2, 28, 1372, 0, '2025-09-08 18:41:07', '2025-09-08 18:41:07', NULL, 1572, 12100);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2941, 45, 117, 1800, 18, 2, 20, 1120, 9, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12516);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2940, 45, 117, 1795, 45, 5, 50, 2800, 8, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12513);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2939, 45, 117, 3956, 18, 2, 20, 1120, 7, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12521);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2938, 45, 117, 1798, 48, 5, 50, 2800, 6, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12512);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2937, 45, 117, 1794, 108, 11, 110, 6160, 5, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12519);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2936, 45, 117, 1799, 48, 5, 50, 2800, 4, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12517);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2935, 45, 117, 3958, 22, 2, 20, 1120, 3, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12523);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2934, 45, 117, 1796, 36, 4, 40, 2240, 2, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12515);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2933, 45, 117, 3957, 11, 1, 10, 560, 1, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12522);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2932, 45, 117, 1797, 38, 4, 40, 2240, 0, '2025-09-12 11:02:13', '2025-09-12 11:02:13', NULL, 1615, 12511);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2703, 46, 3, 608, 24, 2, 20, 1120, 9, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12641);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2702, 46, 3, 626, 18, 2, 20, 1120, 8, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12644);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2701, 46, 3, 604, 16, 2, 20, 1120, 7, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12643);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2700, 46, 3, 3736, 74, 7, 70, 3920, 6, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12793);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2699, 46, 3, 4377, 30, 3, 30, 1680, 5, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12649);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2698, 46, 3, 620, 27, 3, 30, 1680, 4, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12647);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2697, 46, 3, 617, 18, 2, 20, 1120, 3, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12646);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2696, 46, 3, 610, 24, 2, 20, 1120, 2, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12645);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2695, 46, 3, 605, 38, 4, 40, 2240, 1, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12642);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2694, 46, 3, 606, 12, 1, 10, 560, 0, '2025-09-09 18:05:11', '2025-09-09 18:05:11', NULL, 1626, 12640);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2693, 47, 74, 4327, 24, 2, 30, 1044.3, 13, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12457);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2692, 47, 74, 2758, 240, 16, 240, 8354.4, 12, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12447);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2691, 47, 74, 1274, 48, 3, 45, 1566.45, 11, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12444);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2690, 47, 74, 1078, 60, 4, 60, 2088.6, 10, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12443);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2689, 47, 74, 1270, 63, 4, 60, 2088.6, 9, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12446);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2688, 47, 74, 1079, 30, 2, 30, 1044.3, 8, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12442);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2687, 47, 74, 2757, 90, 6, 90, 3132.9, 7, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12445);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2686, 47, 74, 2990, 45, 3, 45, 1566.45, 6, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12449);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2685, 47, 74, 2988, 42, 3, 45, 1566.45, 5, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12448);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2684, 47, 74, 2762, 90, 6, 90, 3132.9, 4, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12451);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2683, 47, 74, 2985, 90, 6, 90, 3132.9, 3, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12450);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2682, 47, 74, 2761, 42, 3, 45, 1566.45, 2, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12452);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2681, 47, 74, 4332, 18, 1, 15, 522.15, 1, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12454);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2680, 47, 74, 4324, 18, 1, 15, 522.15, 0, '2025-09-09 18:04:17', '2025-09-09 18:04:17', NULL, 1610, 12456);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4268, 48, 83, 960, 300, 19, 304, 9603.36, 0, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11992);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4267, 48, 83, 962, 81, 5, 80, 2527.2, 1, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11996);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4266, 48, 83, 963, 32, 2, 32, 1010.88, 2, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11991);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4265, 48, 83, 970, 27, 2, 32, 1010.88, 3, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11995);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4264, 48, 83, 967, 27, 2, 32, 1010.88, 4, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11994);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4263, 48, 83, 972, 180, 11, 176, 5559.84, 5, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11993);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4262, 48, 83, 959, 80, 5, 80, 2527.2, 6, '2025-09-30 18:47:52', '2025-10-10 14:27:22', NULL, 1562, 11997);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2879, 49, 6, 550, 288, 34, 287.3, 11543.71, 0, '2025-09-11 13:46:37', '2025-09-11 13:46:37', NULL, 1627, 12660);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1343, 50, 17, 2671, 180, 15, 180, 9360, 17, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12592);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1342, 50, 17, 2665, 72, 6, 72, 3744, 16, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12593);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1341, 50, 17, 2664, 61, 5, 60, 3120, 15, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12596);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1340, 50, 17, 2659, 48, 4, 48, 2496, 14, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12599);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1339, 50, 17, 2660, 37, 3, 36, 1872, 13, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12600);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1338, 50, 17, 2656, 18, 2, 24, 1248, 12, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12601);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1337, 50, 17, 2652, 66, 6, 72, 3744, 11, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12602);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1336, 50, 17, 2667, 178, 15, 180, 9360, 10, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12603);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1335, 50, 17, 2716, 36, 3, 36, 1872, 9, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12605);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1334, 50, 17, 2654, 60, 5, 60, 3120, 8, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12606);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1333, 50, 17, 2661, 36, 3, 36, 1872, 7, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12607);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1332, 50, 17, 2670, 120, 10, 120, 6240, 6, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12604);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1331, 50, 17, 2650, 12, 1, 12, 624, 5, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12609);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1330, 50, 17, 2714, 60, 5, 60, 3120, 4, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12598);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1329, 50, 17, 2662, 60, 5, 60, 3120, 3, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12616);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1328, 50, 17, 2653, 71, 6, 72, 3744, 2, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12608);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1327, 50, 17, 2657, 60, 5, 60, 3120, 1, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12595);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1326, 50, 17, 2663, 24, 2, 24, 1248, 0, '2025-08-14 18:26:48', '2025-08-14 18:26:48', NULL, 1623, 12594);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2679, 51, 81, 2240, 26, 3, 30, 1004.4, 17, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11805);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2678, 51, 81, 2248, 41, 4, 40, 1339.2, 16, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11804);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2677, 51, 81, 2256, 46, 5, 50, 1674, 15, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11809);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2676, 51, 81, 2254, 60, 6, 60, 2008.8, 14, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11802);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2675, 51, 81, 2246, 47, 5, 50, 1674, 13, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11803);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2674, 51, 81, 2250, 18, 2, 20, 669.6, 12, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11811);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2673, 51, 81, 2253, 38, 4, 40, 1339.2, 11, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11800);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2672, 51, 81, 2242, 31, 3, 30, 1004.4, 10, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11810);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2671, 51, 81, 3993, 7, 1, 10, 334.8, 9, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11816);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2670, 51, 81, 2245, 26, 3, 30, 1004.4, 8, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11806);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2669, 51, 81, 2257, 41, 4, 40, 1339.2, 7, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11807);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2668, 51, 81, 2255, 20, 2, 20, 669.6, 6, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11801);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2667, 51, 81, 2238, 32, 3, 30, 1004.4, 5, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11808);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2666, 51, 81, 2249, 66, 7, 70, 2343.6, 4, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11799);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2665, 51, 81, 2247, 15, 2, 20, 669.6, 3, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11813);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2664, 51, 81, 2241, 49, 5, 50, 1674, 2, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11812);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2663, 51, 81, 2251, 35, 4, 40, 1339.2, 1, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11814);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2662, 51, 81, 3994, 75, 8, 80, 2678.4, 0, '2025-09-09 18:03:34', '2025-09-09 18:03:34', NULL, 1540, 11815);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2661, 52, 55, 1779, 90, 6, 93, 3348, 13, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11598);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2660, 52, 55, 1496, 35, 2, 31, 1116, 12, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11586);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2659, 52, 55, 2801, 79, 5, 77.5, 2790, 11, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11585);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2658, 52, 55, 951, 92, 6, 93, 3348, 10, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11588);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2657, 52, 55, 956, 119, 8, 124, 4464, 9, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11587);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2656, 52, 55, 1770, 154, 10, 155, 5580, 8, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11589);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2655, 52, 55, 1768, 126, 8, 124, 4464, 7, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11590);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2654, 52, 55, 948, 228, 15, 232.5, 8370, 6, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11591);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2653, 52, 55, 1495, 21, 1, 15.5, 558, 5, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11592);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2652, 52, 55, 1767, 85, 5, 77.5, 2790, 4, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11594);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2651, 52, 55, 954, 24, 2, 31, 1116, 3, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11593);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2650, 52, 55, 1769, 70, 5, 77.5, 2790, 2, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11597);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2649, 52, 55, 1772, 57, 4, 62, 2232, 1, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11595);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2648, 52, 55, 958, 10, 1, 15.5, 558, 0, '2025-09-09 18:02:58', '2025-09-09 18:02:58', NULL, 1512, 11596);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2494, 53, 71, 1262, 39, 2, 35.2, 1302.4, 5, '2025-09-08 18:33:35', '2025-09-08 18:33:35', NULL, 1492, 12648);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2493, 53, 71, 1257, 240, 14, 246.4, 9116.8, 4, '2025-09-08 18:33:35', '2025-09-08 18:33:35', NULL, 1492, 12638);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2492, 53, 71, 1261, 102, 6, 105.6, 3907.2, 3, '2025-09-08 18:33:35', '2025-09-08 18:33:35', NULL, 1492, 12637);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2491, 53, 71, 1259, 63, 4, 70.4, 2604.8, 2, '2025-09-08 18:33:35', '2025-09-08 18:33:35', NULL, 1492, 12635);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2490, 53, 71, 1260, 120, 7, 123.2, 4558.4, 1, '2025-09-08 18:33:35', '2025-09-08 18:33:35', NULL, 1492, 12636);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2489, 53, 71, 828, 38, 2, 35.2, 1302.4, 0, '2025-09-08 18:33:35', '2025-09-08 18:33:35', NULL, 1492, 12639);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2488, 54, 69, 1419, 27, 2, 29, 1073, 6, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12000);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2487, 54, 69, 1417, 32, 2, 29, 1073, 5, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12003);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2486, 54, 69, 3767, 48, 3, 43.5, 1609.5, 4, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12006);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2485, 54, 69, 1411, 24, 2, 29, 1073, 3, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12005);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2484, 54, 69, 3769, 48, 3, 43.5, 1609.5, 2, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12002);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2483, 54, 69, 1011, 27, 2, 29, 1073, 1, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12004);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2482, 54, 69, 3768, 60, 4, 58, 2146, 0, '2025-09-08 18:33:02', '2025-09-08 18:33:02', NULL, 1564, 12001);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4563, 55, 85, 97, 47, 5, 50, 2950, 0, '2025-10-07 18:49:05', '2025-10-15 13:20:13', NULL, 1628, 12682);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4562, 55, 85, 100, 55, 6, 60, 3540, 1, '2025-10-07 18:49:05', '2025-10-15 13:20:13', NULL, 1628, 12680);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4561, 55, 85, 98, 123, 12, 120, 7080, 2, '2025-10-07 18:49:05', '2025-10-15 13:20:13', NULL, 1628, 12679);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3376, 56, 88, 1351, 47, 4, 44, 1320, 9, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12620);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3375, 56, 88, 1519, 21, 2, 22, 660, 8, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12622);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3374, 56, 88, 1343, 35, 3, 33, 990, 7, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12623);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3373, 56, 88, 1518, 30, 3, 33, 990, 6, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12624);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3372, 56, 88, 698, 63, 6, 66, 1980, 5, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12629);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3371, 56, 88, 691, 54, 5, 55, 1650, 4, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12628);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3370, 56, 88, 700, 35, 3, 33, 990, 3, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12627);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3369, 56, 88, 696, 61, 6, 66, 1980, 2, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12625);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3368, 56, 88, 692, 21, 2, 22, 660, 1, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12626);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3367, 56, 88, 689, 92, 8, 88, 2640, 0, '2025-09-16 13:00:39', '2025-09-16 13:00:39', NULL, 1625, 12621);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1521, 57, 95, 2208, 73, 8, 76, 2280, 0, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11833);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1522, 57, 95, 2212, 42, 4, 38, 1140, 1, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11817);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1523, 57, 95, 2710, 136, 14, 133, 3990, 2, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11818);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1524, 57, 95, 2194, 265, 28, 266, 7980, 3, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11819);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1525, 57, 95, 2200, 41, 4, 38, 1140, 4, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11820);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1526, 57, 95, 2196, 47, 5, 47.5, 1425, 5, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11821);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1527, 57, 95, 2209, 46, 5, 47.5, 1425, 6, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11822);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1528, 57, 95, 2193, 25, 3, 28.5, 855, 7, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11823);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1529, 57, 95, 2199, 92, 10, 95, 2850, 8, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11824);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1530, 57, 95, 2210, 44, 5, 47.5, 1425, 9, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11825);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1531, 57, 95, 2206, 17, 2, 19, 570, 10, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11827);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1532, 57, 95, 2225, 132, 14, 133, 3990, 11, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11826);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1533, 57, 95, 2203, 16, 2, 19, 570, 12, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11828);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1534, 57, 95, 2224, 36, 4, 38, 1140, 13, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11829);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1535, 57, 95, 2201, 62, 7, 66.5, 1995, 14, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11830);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1536, 57, 95, 2195, 57, 6, 57, 1710, 15, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11832);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1537, 57, 95, 2197, 68, 7, 66.5, 1995, 16, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11831);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1538, 58, 95, 2208, 73, 8, 76, 2280, 0, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11833);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1539, 58, 95, 2212, 42, 4, 38, 1140, 1, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11817);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1540, 58, 95, 2710, 136, 14, 133, 3990, 2, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11818);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1541, 58, 95, 2194, 265, 28, 266, 7980, 3, '2025-08-21 13:40:01', '2025-08-21 13:40:01', NULL, 1541, 11819);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1542, 58, 95, 2200, 41, 4, 38, 1140, 4, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11820);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1543, 58, 95, 2196, 47, 5, 47.5, 1425, 5, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11821);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1544, 58, 95, 2209, 46, 5, 47.5, 1425, 6, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11822);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1545, 58, 95, 2193, 25, 3, 28.5, 855, 7, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11823);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1546, 58, 95, 2199, 92, 10, 95, 2850, 8, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11824);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1547, 58, 95, 2210, 44, 5, 47.5, 1425, 9, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11825);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1548, 58, 95, 2206, 17, 2, 19, 570, 10, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11827);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1549, 58, 95, 2225, 132, 14, 133, 3990, 11, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11826);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1550, 58, 95, 2203, 16, 2, 19, 570, 12, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11828);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1551, 58, 95, 2224, 36, 4, 38, 1140, 13, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11829);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1552, 58, 95, 2201, 62, 7, 66.5, 1995, 14, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11830);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1553, 58, 95, 2195, 57, 6, 57, 1710, 15, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11832);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(1554, 58, 95, 2197, 68, 7, 66.5, 1995, 16, '2025-08-21 13:40:02', '2025-08-21 13:40:02', NULL, 1541, 11831);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2781, 59, 89, 1002, 27, 3, 26.1, 783, 13, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11152);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2780, 59, 89, 999, 28, 3, 26.1, 783, 12, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11151);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2779, 59, 89, 2798, 18, 2, 17.4, 522, 11, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11156);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2778, 59, 89, 1004, 18, 2, 17.4, 522, 10, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11161);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2777, 59, 89, 1003, 45, 5, 43.5, 1305, 9, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11181);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2776, 59, 89, 988, 12, 1, 8.7, 261, 8, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11162);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2775, 59, 89, 995, 50, 6, 52.2, 1566, 7, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11160);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2774, 59, 89, 1377, 27, 3, 26.1, 783, 6, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11159);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2773, 59, 89, 992, 15, 2, 17.4, 522, 5, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11158);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2772, 59, 89, 989, 27, 3, 26.1, 783, 4, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11157);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2771, 59, 89, 1372, 58, 7, 60.9, 1827, 3, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11150);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2770, 59, 89, 1378, 24, 3, 26.1, 783, 2, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11155);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2769, 59, 89, 997, 21, 2, 17.4, 522, 1, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11154);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2768, 59, 89, 993, 16, 2, 17.4, 522, 0, '2025-09-10 14:48:19', '2025-09-10 14:48:19', NULL, 1401, 11153);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2644, 60, 93, 3356, 19, 2, 21, 819, 24, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12049);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2643, 60, 93, 3376, 9, 1, 10.5, 409.5, 23, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12048);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2642, 60, 93, 3353, 12, 1, 10.5, 409.5, 22, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12047);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2641, 60, 93, 3394, 25, 2, 21, 819, 21, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12046);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2640, 60, 93, 3386, 19, 2, 21, 819, 20, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12045);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2639, 60, 93, 3335, 33, 3, 31.5, 1228.5, 19, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12053);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2638, 60, 93, 3326, 8, 1, 10.5, 409.5, 18, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12052);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2637, 60, 93, 3384, 8, 1, 10.5, 409.5, 17, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12054);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2636, 60, 93, 3371, 34, 3, 31.5, 1228.5, 16, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12051);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2635, 60, 93, 3339, 21, 2, 21, 819, 15, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12050);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2634, 60, 93, 3375, 17, 2, 21, 819, 14, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12055);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2633, 60, 93, 3349, 7, 1, 10.5, 409.5, 13, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12056);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2632, 60, 93, 3355, 15, 1, 10.5, 409.5, 12, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12057);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2631, 60, 93, 3333, 36, 3, 31.5, 1228.5, 11, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12058);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2630, 60, 93, 3399, 46, 4, 42, 1638, 10, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12059);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2629, 60, 93, 3390, 32, 3, 31.5, 1228.5, 9, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12044);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2628, 60, 93, 3346, 6, 1, 10.5, 409.5, 8, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12060);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2627, 60, 93, 3370, 7, 1, 10.5, 409.5, 7, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12043);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2626, 60, 93, 3520, 59, 6, 63, 2457, 6, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12036);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2625, 60, 93, 3372, 22, 2, 21, 819, 5, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12032);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2624, 60, 93, 3345, 30, 3, 31.5, 1228.5, 4, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12037);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2623, 60, 93, 3373, 42, 4, 42, 1638, 3, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12033);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2622, 60, 93, 3332, 18, 2, 21, 819, 2, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12038);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2621, 60, 93, 3374, 5, 1, 10.5, 409.5, 1, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12039);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2620, 60, 93, 3383, 206, 20, 210, 8190, 0, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12040);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2619, 60, 92, 3360, 11, 1, 11, 429, 27, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12042);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2618, 60, 92, 3336, 10, 1, 11, 429, 26, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12041);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2617, 60, 92, 3389, 54, 5, 55, 2145, 25, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12035);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2616, 60, 92, 3363, 25, 2, 22, 858, 24, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12030);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2615, 60, 92, 3393, 14, 1, 11, 429, 23, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12029);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2614, 60, 92, 3378, 14, 1, 11, 429, 22, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12031);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2613, 60, 92, 3334, 19, 2, 22, 858, 21, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12027);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2612, 60, 92, 3377, 34, 3, 33, 1287, 20, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12028);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2611, 60, 92, 3320, 9, 1, 11, 429, 19, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12025);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2610, 60, 92, 3382, 19, 2, 22, 858, 18, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12026);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2609, 60, 92, 3357, 34, 3, 33, 1287, 17, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12023);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2608, 60, 92, 3358, 23, 2, 22, 858, 16, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12024);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2607, 60, 92, 3325, 17, 2, 22, 858, 15, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12022);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2606, 60, 92, 3331, 23, 2, 22, 858, 14, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12020);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2605, 60, 92, 3387, 44, 4, 44, 1716, 13, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12021);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2604, 60, 92, 3327, 8, 1, 11, 429, 12, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12019);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2603, 60, 92, 3338, 27, 2, 22, 858, 11, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12018);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2602, 60, 92, 3341, 13, 1, 11, 429, 10, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12015);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2601, 60, 92, 3362, 25, 2, 22, 858, 9, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12016);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2600, 60, 92, 3343, 34, 3, 33, 1287, 8, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12017);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2599, 60, 92, 3321, 19, 2, 22, 858, 7, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12034);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2598, 60, 92, 3366, 84, 8, 88, 3432, 6, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12012);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2597, 60, 92, 3388, 7, 1, 11, 429, 5, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12013);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2596, 60, 92, 3368, 41, 4, 44, 1716, 4, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12011);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2595, 60, 92, 3369, 19, 2, 22, 858, 3, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12010);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2594, 60, 92, 3319, 8, 1, 11, 429, 2, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12008);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2593, 60, 92, 3385, 16, 1, 11, 429, 1, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12009);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2592, 60, 92, 3324, 54, 5, 55, 2145, 0, '2025-09-09 17:59:04', '2025-09-09 17:59:04', NULL, 1566, 12014);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4575, 61, 136, 1344, 28.8, 3, 28.5, 1111.5, 0, '2025-10-08 12:39:07', '2025-10-24 17:22:06', NULL, 1519, 11638);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4574, 61, 136, 216, 60, 6, 57, 2223, 1, '2025-10-08 12:39:07', '2025-10-24 17:22:06', NULL, 1519, 11634);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4573, 61, 136, 1730, 24, 3, 28.5, 1111.5, 2, '2025-10-08 12:39:07', '2025-10-24 17:22:06', NULL, 1519, 11633);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4572, 61, 136, 218, 18, 2, 19, 741, 3, '2025-10-08 12:39:07', '2025-10-24 17:22:06', NULL, 1519, 11636);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4571, 61, 136, 1342, 62.4, 7, 66.5, 2593.5, 4, '2025-10-08 12:39:07', '2025-10-24 17:22:06', NULL, 1519, 11635);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4570, 61, 136, 214, 22.8, 2, 19, 741, 5, '2025-10-08 12:39:07', '2025-10-24 17:22:06', NULL, 1519, 11637);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2577, 62, 17, 2665, 72, 6, 72, 3960, 17, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12593);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2576, 62, 17, 2657, 60, 5, 60, 3300, 16, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12595);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2575, 62, 17, 2664, 61, 5, 60, 3300, 15, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12596);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2574, 62, 17, 2714, 60, 5, 60, 3300, 14, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12598);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2573, 62, 17, 2660, 37, 3, 36, 1980, 13, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12600);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2572, 62, 17, 2716, 36, 3, 36, 1980, 12, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12605);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2571, 62, 17, 2656, 18, 2, 24, 1320, 11, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12601);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2570, 62, 17, 2670, 120, 10, 120, 6600, 10, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12604);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2569, 62, 17, 2652, 66, 6, 72, 3960, 9, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12602);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2568, 62, 17, 2659, 48, 4, 48, 2640, 8, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12599);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2567, 62, 17, 2663, 24, 2, 24, 1320, 7, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12594);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2566, 62, 17, 2667, 178, 15, 180, 9900, 6, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12603);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2565, 62, 17, 2661, 36, 3, 36, 1980, 5, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12607);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2564, 62, 17, 2653, 71, 6, 72, 3960, 4, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12608);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2563, 62, 17, 2662, 60, 5, 60, 3300, 3, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12616);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2562, 62, 17, 2650, 12, 1, 12, 660, 2, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12609);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2561, 62, 17, 2654, 60, 5, 60, 3300, 1, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12606);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2560, 62, 17, 2671, 180, 15, 180, 9900, 0, '2025-09-08 18:47:48', '2025-09-08 18:47:48', NULL, 1623, 12592);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4504, 63, 72, 1109, 55, 3, 49.5, 1831.5, 5, '2025-10-06 14:06:05', '2025-10-06 14:06:05', NULL, 1403, 11499);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4503, 63, 72, 1106, 84, 5, 82.5, 3052.5, 4, '2025-10-06 14:06:05', '2025-10-06 14:06:05', NULL, 1403, 11497);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4502, 63, 72, 1111, 87, 5, 82.5, 3052.5, 3, '2025-10-06 14:06:05', '2025-10-06 14:06:05', NULL, 1403, 11496);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4501, 63, 72, 1112, 63, 4, 66, 2442, 2, '2025-10-06 14:06:05', '2025-10-06 14:06:05', NULL, 1403, 11495);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4500, 63, 72, 1113, 458, 28, 462, 17094, 1, '2025-10-06 14:06:05', '2025-10-06 14:06:05', NULL, 1403, 11498);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4499, 63, 72, 1104, 147, 9, 148.5, 5494.5, 0, '2025-10-06 14:06:05', '2025-10-06 14:06:05', NULL, 1403, 11501);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2591, 64, 39, 2334, 75, 7, 71.4, 2427.6, 13, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12227);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2590, 64, 39, 2333, 55, 5, 51, 1734, 12, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12226);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2589, 64, 39, 2335, 55, 5, 51, 1734, 11, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12228);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2588, 64, 39, 2332, 80, 8, 81.6, 2774.4, 10, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12225);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2587, 64, 39, 2337, 62, 6, 61.2, 2080.8, 9, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12229);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2586, 64, 39, 2326, 65, 6, 61.2, 2080.8, 8, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12233);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2585, 64, 39, 2338, 53, 5, 51, 1734, 7, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12230);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2584, 64, 39, 2339, 65, 6, 61.2, 2080.8, 6, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12231);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2583, 64, 39, 2329, 70, 7, 71.4, 2427.6, 5, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12222);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2582, 64, 39, 2330, 79, 8, 81.6, 2774.4, 4, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12223);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2581, 64, 39, 2325, 62, 6, 61.2, 2080.8, 3, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12221);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2580, 64, 39, 2324, 94, 9, 91.8, 3121.2, 2, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12220);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2579, 64, 39, 2336, 31, 3, 30.6, 1040.4, 1, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12232);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2578, 64, 39, 2331, 65, 6, 61.2, 2080.8, 0, '2025-09-09 17:56:31', '2025-09-09 17:56:31', NULL, 1584, 12224);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4498, 65, 110, 2399, 216, 21, 220.5, 7382.34, 0, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12830);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4497, 65, 110, 2408, 139, 13, 136.5, 4570.02, 1, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12831);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4496, 65, 110, 2386, 288, 27, 283.5, 9491.58, 2, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12828);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4495, 65, 110, 2417, 192, 18, 189, 6327.72, 3, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12825);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4494, 65, 109, 2396, 600, 41, 594.5, 19903.86, 0, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12829);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4493, 65, 109, 2410, 216, 15, 217.5, 7281.9, 1, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12827);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4492, 65, 82, 2390, 96, 6, 96, 3214.08, 0, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12815);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4491, 65, 82, 2400, 48, 3, 48, 1607.04, 1, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12810);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4490, 65, 82, 2411, 48, 3, 48, 1607.04, 2, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12803);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4489, 65, 82, 2389, 96, 6, 96, 3214.08, 3, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12826);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4488, 65, 82, 2407, 216, 14, 224, 7499.52, 4, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12822);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4487, 65, 82, 2406, 264, 17, 272, 9106.56, 5, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12821);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4486, 65, 82, 2413, 74, 5, 80, 2678.4, 6, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12823);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4485, 65, 80, 4401, 48, 5, 50, 1674, 0, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12804);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4484, 65, 80, 4406, 48, 5, 50, 1674, 1, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12819);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4483, 65, 80, 2398, 168, 17, 170, 5691.6, 2, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12816);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4482, 65, 80, 2422, 199, 20, 200, 6696, 3, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12820);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4481, 65, 80, 2388, 156, 16, 160, 5356.8, 4, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12824);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4480, 65, 80, 4402, 48, 5, 50, 1674, 5, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12809);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4479, 65, 80, 4404, 72, 7, 70, 2343.6, 6, '2025-10-06 14:05:28', '2025-10-09 19:42:54', NULL, 1633, 12802);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3763, 66, 102, 2552, 64, 5, 60, 2280, 0, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12123);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3762, 66, 102, 2553, 77, 6, 72, 2736, 1, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12124);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3761, 66, 102, 2550, 91, 8, 96, 3648, 2, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12128);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3760, 66, 102, 2551, 397, 33, 396, 15048, 3, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12129);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3759, 66, 102, 2554, 44, 4, 48, 1824, 4, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12130);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3758, 66, 102, 2545, 233, 19, 228, 8664, 5, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12131);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3757, 66, 102, 2546, 76, 6, 72, 2736, 6, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12132);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3756, 66, 102, 2547, 415, 35, 420, 15960, 7, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12125);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3755, 66, 102, 2549, 181, 15, 180, 6840, 8, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12127);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3754, 66, 102, 2548, 86, 7, 84, 3192, 9, '2025-09-19 19:38:25', '2025-10-17 19:25:49', NULL, 1575, 12126);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2973, 67, 15, 909, 80, 8, 78.4, 2965.87, 6, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11547);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2972, 67, 15, 921, 34, 3, 29.4, 1112.2, 5, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11550);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2971, 67, 15, 924, 40, 4, 39.2, 1482.94, 4, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11542);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2970, 67, 15, 928, 148, 15, 147, 5561.01, 3, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11549);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2969, 67, 15, 927, 77, 8, 78.4, 2965.87, 2, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11551);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2968, 67, 15, 922, 140, 14, 137.2, 5190.28, 1, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11543);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2967, 67, 15, 919, 175, 18, 176.4, 6673.21, 0, '2025-09-12 15:02:25', '2025-09-12 15:02:25', NULL, 1483, 11548);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4592, 68, 134, 2195, 57, 5, 57.5, 1725, 0, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11832);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4591, 68, 134, 2197, 68, 6, 69, 2070, 1, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11831);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4590, 68, 134, 2224, 36, 3, 34.5, 1035, 2, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11829);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4589, 68, 134, 2201, 62, 5, 57.5, 1725, 3, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11830);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4588, 68, 134, 2206, 17, 2, 23, 690, 4, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11827);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4587, 68, 134, 2203, 16, 1, 11.5, 345, 5, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11828);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4586, 68, 134, 2225, 132, 11, 126.5, 3795, 6, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11826);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4585, 68, 134, 2210, 44, 4, 46, 1380, 7, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11825);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4584, 68, 134, 2199, 92, 8, 92, 2760, 8, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11824);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4583, 68, 134, 2193, 25, 2, 23, 690, 9, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11823);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4582, 68, 134, 2209, 46, 4, 46, 1380, 10, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11822);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4581, 68, 134, 2196, 47, 4, 46, 1380, 11, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11821);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4580, 68, 134, 2194, 265, 23, 264.5, 7935, 12, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11819);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4579, 68, 134, 2212, 42, 4, 46, 1380, 13, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11817);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4578, 68, 134, 2710, 136, 12, 138, 4140, 14, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11818);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4577, 68, 134, 2200, 41, 4, 46, 1380, 15, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11820);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4576, 68, 134, 2208, 73, 6, 69, 2070, 16, '2025-10-08 12:44:44', '2025-10-15 13:35:11', NULL, 1541, 11833);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2717, 69, 14, 531, 31.2, 3, 30, 1635, 0, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12463);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2716, 69, 14, 527, 16.8, 2, 20, 1090, 1, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12466);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2715, 69, 14, 532, 60, 6, 60, 3270, 2, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12467);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2714, 69, 14, 1975, 150, 15, 150, 8175, 3, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12465);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2713, 69, 14, 537, 53.4, 5, 50, 2725, 4, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12468);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2712, 69, 14, 1980, 24, 2, 20, 1090, 5, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12469);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2711, 69, 14, 545, 72, 7, 70, 3815, 6, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12464);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2710, 69, 14, 535, 218.4, 22, 220, 11990, 7, '2025-09-10 11:51:44', '2025-10-24 20:19:32', NULL, 1612, 12462);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3322, 70, 118, 153, 41, 4, 40.8, 1591.2, 5, '2025-09-16 12:57:29', '2025-09-16 12:57:29', NULL, 1624, 12610);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3321, 70, 118, 163, 66, 6, 61.2, 2386.8, 4, '2025-09-16 12:57:29', '2025-09-16 12:57:29', NULL, 1624, 12612);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3320, 70, 118, 149, 34, 3, 30.6, 1193.4, 3, '2025-09-16 12:57:29', '2025-09-16 12:57:29', NULL, 1624, 12615);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3319, 70, 118, 156, 66, 6, 61.2, 2386.8, 2, '2025-09-16 12:57:29', '2025-09-16 12:57:29', NULL, 1624, 12614);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3318, 70, 118, 1556, 34, 3, 30.6, 1193.4, 1, '2025-09-16 12:57:29', '2025-09-16 12:57:29', NULL, 1624, 12613);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3317, 70, 118, 165, 84, 8, 81.6, 3182.4, 0, '2025-09-16 12:57:29', '2025-09-16 12:57:29', NULL, 1624, 12611);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3599, 71, 120, 1857, 57, 5, 52.5, 2047.5, 0, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11957);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3598, 71, 120, 1856, 41, 4, 42, 1638, 1, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11956);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3597, 71, 120, 1854, 63, 6, 63, 2457, 2, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11955);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3596, 71, 120, 1851, 61, 6, 63, 2457, 3, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11954);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3595, 71, 120, 1850, 61, 6, 63, 2457, 4, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11938);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3594, 71, 120, 1847, 22, 2, 21, 819, 5, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11935);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3593, 71, 120, 1849, 52, 5, 52.5, 2047.5, 6, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11937);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3592, 71, 120, 1846, 52, 5, 52.5, 2047.5, 7, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11934);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3591, 71, 120, 1848, 92, 9, 94.5, 3685.5, 8, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11936);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3590, 71, 120, 1842, 40, 4, 42, 1638, 9, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11932);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3589, 71, 120, 1845, 75, 7, 73.5, 2866.5, 10, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11933);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3588, 71, 120, 1840, 90, 9, 94.5, 3685.5, 11, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11930);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3587, 71, 120, 1839, 78, 7, 73.5, 2866.5, 12, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11929);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3586, 71, 120, 1841, 48, 5, 52.5, 2047.5, 13, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11931);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3585, 71, 120, 1838, 37, 4, 42, 1638, 14, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11928);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3584, 71, 120, 1837, 103, 10, 105, 4095, 15, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11927);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3583, 71, 120, 1834, 55, 5, 52.5, 2047.5, 16, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11926);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3582, 71, 120, 1833, 38, 4, 42, 1638, 17, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11925);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3581, 71, 120, 1832, 36, 3, 31.5, 1228.5, 18, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11924);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3580, 71, 120, 1831, 65, 6, 63, 2457, 19, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11923);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3579, 71, 120, 1830, 72, 7, 73.5, 2866.5, 20, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11922);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3578, 71, 120, 1829, 75, 7, 73.5, 2866.5, 21, '2025-09-17 18:04:44', '2025-10-10 18:28:14', NULL, 1557, 11917);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3358, 72, 119, 1188, 40, 3, 34.8, 1357.2, 17, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12240);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3357, 72, 119, 1187, 44, 4, 46.4, 1809.6, 16, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12237);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3356, 72, 119, 1185, 23, 2, 23.2, 904.8, 15, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12235);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3355, 72, 119, 1209, 19, 2, 23.2, 904.8, 14, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12236);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3354, 72, 119, 1211, 16, 1, 11.6, 452.4, 13, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12238);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3353, 72, 119, 1193, 36, 3, 34.8, 1357.2, 12, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12834);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3352, 72, 119, 1205, 34, 3, 34.8, 1357.2, 11, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12833);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3351, 72, 119, 1186, 17, 2, 23.2, 904.8, 10, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12241);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3350, 72, 119, 4411, 15, 1, 11.6, 452.4, 9, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12800);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3349, 72, 119, 4412, 18, 2, 23.2, 904.8, 8, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12799);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3348, 72, 119, 1195, 39, 3, 34.8, 1357.2, 7, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12249);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3347, 72, 119, 4008, 54, 5, 58, 2262, 6, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12250);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3346, 72, 119, 1208, 66, 6, 69.6, 2714.4, 5, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12242);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3345, 72, 119, 1194, 23, 2, 23.2, 904.8, 4, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12243);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3344, 72, 119, 1189, 38, 3, 34.8, 1357.2, 3, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12244);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3343, 72, 119, 1204, 14, 1, 11.6, 452.4, 2, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12246);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3342, 72, 119, 1192, 14, 1, 11.6, 452.4, 1, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12247);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3341, 72, 119, 1207, 35, 3, 34.8, 1357.2, 0, '2025-09-16 12:58:46', '2025-09-16 12:58:46', NULL, 1585, 12248);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3310, 73, 54, 3480, 172.8, 13, 175.5, 6669, 0, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11872);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3309, 73, 54, 3470, 43.2, 3, 40.5, 1539, 1, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11871);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3308, 73, 54, 3438, 28.8, 2, 27, 1026, 2, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11867);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3307, 73, 54, 3469, 15.6, 1, 13.5, 513, 3, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11868);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3306, 73, 54, 3463, 13.8, 1, 13.5, 513, 4, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11873);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3305, 73, 54, 3434, 72, 5, 67.5, 2565, 5, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11875);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3304, 73, 54, 3472, 18, 1, 13.5, 513, 6, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11874);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3303, 73, 54, 3481, 188.4, 14, 189, 7182, 7, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 12257);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3302, 73, 54, 3425, 145.2, 11, 148.5, 5643, 8, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11869);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3301, 73, 54, 3437, 31.2, 2, 27, 1026, 9, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11866);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3300, 73, 54, 3464, 40.8, 3, 40.5, 1539, 10, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11876);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3299, 73, 51, 3466, 154.2, 13, 156, 5928, 0, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11858);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3298, 73, 51, 3431, 60, 5, 60, 2280, 1, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11856);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3297, 73, 51, 3439, 67.2, 6, 72, 2736, 2, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11857);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3296, 73, 51, 3457, 40.2, 3, 36, 1368, 3, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11863);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3295, 73, 51, 3460, 60, 5, 60, 2280, 4, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11862);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3294, 73, 51, 3461, 53.4, 4, 48, 1824, 5, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11861);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3293, 73, 51, 3441, 38.4, 3, 36, 1368, 6, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11860);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3292, 73, 51, 3435, 41.4, 3, 36, 1368, 7, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11859);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3291, 73, 51, 3426, 197.4, 16, 192, 7296, 8, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11855);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3290, 73, 46, 3424, 291, 26, 286, 10868, 0, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11846);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3289, 73, 46, 3429, 187.2, 17, 187, 7106, 1, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11849);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3288, 73, 46, 3430, 105, 10, 110, 4180, 2, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11845);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3287, 73, 46, 3428, 52.2, 5, 55, 2090, 3, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11847);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3286, 73, 46, 3442, 92.4, 8, 88, 3344, 4, '2025-09-16 12:23:09', '2025-10-22 19:28:22', NULL, 1546, 11852);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3362, 74, 58, 4275, 10.8, 1, 9, 324, 0, '2025-09-16 12:59:27', '2025-10-28 17:54:48', NULL, 1619, 12792);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3361, 74, 58, 4274, 6, 1, 9, 324, 1, '2025-09-16 12:59:27', '2025-10-28 17:54:48', NULL, 1619, 12789);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3360, 74, 58, 3024, 94.2, 10, 90, 3240, 2, '2025-09-16 12:59:27', '2025-10-28 17:54:48', NULL, 1619, 12790);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3359, 74, 58, 3020, 112.2, 12, 108, 3888, 3, '2025-09-16 12:59:27', '2025-10-28 17:54:48', NULL, 1619, 12791);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2999, 75, 121, 4032, 36, 2, 29, 1165.22, 0, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12543);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2998, 75, 121, 4120, 30, 2, 29, 1165.22, 1, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12184);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2997, 75, 121, 4200, 24, 2, 29, 1165.22, 2, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12219);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2996, 75, 121, 2823, 72, 5, 72.5, 2913.05, 3, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 11616);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2995, 75, 121, 4354, 120, 8, 116, 4660.88, 4, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12553);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2994, 75, 121, 1805, 24, 2, 29, 1165.22, 5, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12215);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2993, 75, 121, 4071, 90, 6, 87, 3495.66, 6, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12546);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2992, 75, 121, 1817, 30, 2, 29, 1165.22, 7, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 11620);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2991, 75, 121, 4187, 28, 2, 29, 1165.22, 8, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12217);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2990, 75, 121, 4362, 48, 3, 43.5, 1747.83, 9, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12554);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2989, 75, 121, 4152, 36, 2, 29, 1165.22, 10, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12181);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2988, 75, 121, 1807, 48, 3, 43.5, 1747.83, 11, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 11628);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2987, 75, 121, 1819, 36, 2, 29, 1165.22, 12, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 11629);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2986, 75, 121, 4043, 24, 2, 29, 1165.22, 13, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 12175);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2985, 75, 121, 1802, 36, 2, 29, 1165.22, 14, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 11619);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2984, 75, 121, 1808, 36, 2, 29, 1165.22, 15, '2025-09-12 16:22:10', '2025-10-20 19:03:58', NULL, 1516, 11624);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2983, 75, 121, 1822, 30, 2, 29, 1165.22, 16, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 11615);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2982, 75, 121, 4253, 30, 2, 29, 1165.22, 17, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12182);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2981, 75, 121, 4054, 24, 2, 29, 1165.22, 18, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12205);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2980, 75, 121, 4106, 27, 2, 29, 1165.22, 19, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12544);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2979, 75, 121, 1811, 23, 2, 29, 1165.22, 20, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 11621);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2978, 75, 121, 4361, 30, 2, 29, 1165.22, 21, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12555);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2977, 75, 121, 4360, 30, 2, 29, 1165.22, 22, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12587);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2976, 75, 121, 4357, 24, 2, 29, 1165.22, 23, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12584);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2975, 75, 121, 4353, 36, 2, 29, 1165.22, 24, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12569);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(2974, 75, 121, 4359, 33, 2, 29, 1165.22, 25, '2025-09-12 16:22:10', '2025-10-20 19:03:59', NULL, 1516, 12586);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3555, 76, 47, 761, 90, 3, 79.5, 2862, 0, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11898);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3554, 76, 47, 744, 48, 2, 53, 1908, 1, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11897);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3553, 76, 47, 755, 48, 2, 53, 1908, 2, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11904);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3552, 76, 47, 751, 31.2, 1, 26.5, 954, 3, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11903);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3551, 76, 47, 746, 84, 3, 79.5, 2862, 4, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11907);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3550, 76, 47, 1750, 42, 2, 53, 1908, 5, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11909);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3549, 76, 47, 745, 48, 2, 53, 1908, 6, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11906);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3548, 76, 47, 757, 36, 1, 26.5, 954, 7, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11911);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3547, 76, 47, 753, 276, 10, 265, 9540, 8, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11908);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3546, 76, 47, 760, 156, 6, 159, 5724, 9, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11910);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3545, 76, 47, 747, 24, 1, 26.5, 954, 10, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11899);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3544, 76, 47, 759, 72, 3, 79.5, 2862, 11, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11905);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3543, 76, 47, 754, 45, 2, 53, 1908, 12, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11901);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3542, 76, 47, 1751, 108, 4, 106, 3816, 13, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11900);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3541, 76, 47, 748, 66, 2, 53, 1908, 14, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11902);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3540, 76, 47, 749, 186, 7, 185.5, 6678, 15, '2025-09-17 17:37:39', '2025-10-28 17:52:50', NULL, 1556, 11896);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3454, 77, 122, 3783, 52, 7, 49, 2295.16, 9, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11971);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3453, 77, 122, 1934, 58, 8, 56, 2623.04, 8, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11966);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3452, 77, 122, 2310, 67, 10, 70, 3278.8, 7, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11965);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3451, 77, 122, 1937, 26, 4, 28, 1311.52, 6, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11970);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3450, 77, 122, 4245, 50, 7, 49, 2295.16, 5, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 12496);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3449, 77, 122, 4246, 53, 8, 56, 2623.04, 4, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 12371);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3448, 77, 122, 1933, 25, 4, 28, 1311.52, 3, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11968);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3447, 77, 122, 1938, 19, 3, 21, 983.64, 2, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11969);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3446, 77, 122, 1944, 52, 7, 49, 2295.16, 1, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11998);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3445, 77, 122, 1946, 56, 8, 56, 2623.04, 0, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 12370);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3444, 77, 26, 1935, 54, 7, 56, 2623.04, 6, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11962);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3443, 77, 26, 1945, 58, 7, 56, 2623.04, 5, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11961);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3442, 77, 26, 1932, 66, 8, 64, 2997.76, 4, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11959);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3441, 77, 26, 2768, 73, 9, 72, 3372.48, 3, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11960);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3440, 77, 26, 1939, 73, 9, 72, 3372.48, 2, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11963);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3439, 77, 26, 1936, 71, 9, 72, 3372.48, 1, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11967);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3438, 77, 26, 2311, 55, 7, 56, 2623.04, 0, '2025-09-16 17:13:05', '2025-09-16 17:13:05', NULL, 1560, 11964);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4000, 78, 25, 3045, 146.4, 21, 147, 6885.48, 0, '2025-09-26 13:11:05', '2025-10-28 14:08:44', NULL, 1571, 12086);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3999, 78, 25, 1063, 171.6, 25, 175, 8197, 1, '2025-09-26 13:11:05', '2025-10-28 14:08:44', NULL, 1571, 12087);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3998, 78, 25, 3055, 150, 21, 147, 6885.48, 2, '2025-09-26 13:11:05', '2025-10-28 14:08:44', NULL, 1571, 12088);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3997, 78, 25, 1065, 132, 19, 133, 6229.72, 3, '2025-09-26 13:11:05', '2025-10-28 14:08:44', NULL, 1571, 12085);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3996, 79, 86, 1222, 48, 3, 43.5, 2037.54, 14, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12440);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3995, 79, 86, 1221, 30, 2, 29, 1358.36, 13, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12439);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3994, 79, 86, 1762, 12, 1, 14.5, 679.18, 12, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12432);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3993, 79, 86, 1226, 24, 2, 29, 1358.36, 11, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12429);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3992, 79, 86, 1757, 60, 4, 58, 2716.72, 10, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12441);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3991, 79, 86, 1225, 66, 5, 72.5, 3395.9, 9, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12428);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3990, 79, 86, 1761, 30, 2, 29, 1358.36, 8, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12510);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3989, 79, 86, 1760, 36, 2, 29, 1358.36, 7, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12518);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3988, 79, 86, 3595, 72, 5, 72.5, 3395.9, 6, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12514);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3987, 79, 86, 3591, 90, 6, 87, 4075.08, 5, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12520);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3986, 79, 86, 4346, 90, 6, 87, 4075.08, 4, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12664);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3985, 79, 86, 1224, 42, 3, 43.5, 2037.54, 3, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12425);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3984, 79, 86, 1220, 72, 5, 72.5, 3395.9, 2, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12423);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3983, 79, 86, 1214, 66, 5, 72.5, 3395.9, 1, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12426);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3982, 79, 86, 3590, 72, 5, 72.5, 3395.9, 0, '2025-09-26 13:10:41', '2025-09-26 13:10:41', NULL, 1608, 12434);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3942, 80, 130, 4005, 55.8, 5, 53, 2981.25, 0, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11668);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3941, 80, 130, 318, 52.2, 5, 53, 2981.25, 1, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11663);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3940, 80, 130, 307, 97.2, 9, 95.4, 5366.25, 2, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11665);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3939, 80, 130, 315, 49.2, 5, 53, 2981.25, 3, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11664);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3938, 80, 130, 309, 63, 6, 63.6, 3577.5, 4, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11667);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3937, 80, 130, 308, 61.2, 6, 63.6, 3577.5, 5, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11666);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3936, 80, 130, 4007, 43.2, 4, 42.4, 2385, 6, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11670);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3935, 80, 130, 314, 49.2, 5, 53, 2981.25, 7, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11662);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3934, 80, 130, 316, 61.2, 6, 63.6, 3577.5, 8, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11661);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3933, 80, 130, 311, 52.8, 5, 53, 2981.25, 9, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11659);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3932, 80, 130, 306, 85.2, 8, 84.8, 4770, 10, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11660);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3931, 80, 130, 4006, 61.8, 6, 63.6, 3577.5, 11, '2025-09-24 14:36:29', '2025-10-22 19:29:10', NULL, 1528, 11669);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4188, 81, 124, 1449, 12, 1, 10, 562.5, 0, '2025-09-30 12:53:59', '2025-10-10 12:16:06', NULL, 1622, 12563);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4187, 81, 124, 1444, 30, 3, 30, 1687.5, 1, '2025-09-30 12:53:59', '2025-10-10 12:16:06', NULL, 1622, 12562);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4186, 81, 124, 1441, 30, 3, 30, 1687.5, 2, '2025-09-30 12:53:59', '2025-10-10 12:16:06', NULL, 1622, 12573);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4185, 81, 124, 2473, 180, 18, 180, 10125, 3, '2025-09-30 12:53:59', '2025-10-10 12:16:06', NULL, 1622, 12565);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4172, 82, 13, 3255, 10.8, 2, 14, 787.5, 0, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12361);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4171, 82, 13, 3290, 57, 8, 56, 3150, 1, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12353);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4170, 82, 13, 1860, 33, 5, 35, 1968.75, 2, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12354);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4169, 82, 13, 2573, 90, 13, 91, 5118.75, 3, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12356);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4168, 82, 13, 1859, 9, 1, 7, 393.75, 4, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12357);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4167, 82, 13, 1858, 39, 6, 42, 2362.5, 5, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12359);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4166, 82, 13, 4375, 297, 42, 294, 16537.5, 6, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12570);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4165, 82, 13, 3256, 36, 5, 35, 1968.75, 7, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12362);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4164, 82, 13, 2571, 51, 7, 49, 2756.25, 8, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12364);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4163, 82, 13, 3257, 18, 3, 21, 1181.25, 9, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12363);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4162, 82, 13, 2570, 42.6, 6, 42, 2362.5, 10, '2025-09-29 19:08:30', '2025-10-30 12:08:47', NULL, 1597, 12360);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3705, 83, 101, 2506, 91.2, 8, 96, 5760, 0, '2025-09-19 17:40:36', '2025-10-28 18:28:26', NULL, 1588, 12259);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3704, 83, 101, 2508, 24, 2, 24, 1440, 1, '2025-09-19 17:40:36', '2025-10-28 18:28:26', NULL, 1588, 12260);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3703, 83, 101, 2505, 627.6, 52, 624, 37440, 2, '2025-09-19 17:40:36', '2025-10-28 18:28:26', NULL, 1588, 12258);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4130, 84, 94, 978, 134, 17, 136, 5304, 0, '2025-09-29 16:52:35', '2025-10-17 17:26:38', NULL, 1631, 12676);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4129, 84, 94, 982, 27, 3, 24, 936, 1, '2025-09-29 16:52:35', '2025-10-17 17:26:38', NULL, 1631, 12674);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4128, 84, 94, 985, 474, 59, 472, 18408, 2, '2025-09-29 16:52:35', '2025-10-17 17:26:38', NULL, 1631, 12675);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4127, 84, 94, 981, 27, 3, 24, 936, 3, '2025-09-29 16:52:35', '2025-10-17 17:26:38', NULL, 1631, 12673);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4126, 84, 94, 976, 44, 6, 48, 1872, 4, '2025-09-29 16:52:35', '2025-10-17 17:26:38', NULL, 1631, 12677);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3743, 85, 125, 1404, 69.6, 9, 67.5, 3645, 0, '2025-09-19 19:01:01', '2025-10-24 15:01:24', NULL, 1530, 11709);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3742, 85, 125, 1410, 134.4, 18, 135, 7290, 1, '2025-09-19 19:01:01', '2025-10-24 15:01:24', NULL, 1530, 11707);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3741, 85, 125, 1403, 60, 8, 60, 3240, 2, '2025-09-19 19:01:01', '2025-10-24 15:01:24', NULL, 1530, 11708);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3740, 85, 125, 1398, 115.2, 15, 112.5, 6075, 3, '2025-09-19 19:01:01', '2025-10-24 15:01:24', NULL, 1530, 11710);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3739, 85, 125, 1396, 30, 4, 30, 1620, 4, '2025-09-19 19:01:01', '2025-10-24 15:01:24', NULL, 1530, 11711);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3930, 86, 66, 688, 41, 5, 40, 1423.6, 10, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11972);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3929, 86, 66, 685, 53, 7, 56, 1993.04, 9, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11990);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3928, 86, 66, 674, 59, 7, 56, 1993.04, 8, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11974);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3927, 86, 66, 686, 53, 7, 56, 1993.04, 7, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11981);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3926, 86, 66, 678, 42, 5, 40, 1423.6, 6, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11984);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3925, 86, 66, 671, 40, 5, 40, 1423.6, 5, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11985);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3924, 86, 66, 664, 35, 4, 32, 1138.88, 4, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11986);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3923, 86, 66, 679, 24, 3, 24, 854.16, 3, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11987);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3922, 86, 66, 3918, 193, 24, 192, 6833.28, 2, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11983);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3921, 86, 66, 661, 58, 7, 56, 1993.04, 1, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11989);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3920, 86, 66, 683, 55, 7, 56, 1993.04, 0, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11988);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3919, 86, 64, 659, 180, 21, 178.5, 6352.82, 7, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11982);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3918, 86, 64, 684, 19, 2, 17, 605.03, 6, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11973);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3917, 86, 64, 662, 24, 3, 25.5, 907.55, 5, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11975);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3916, 86, 64, 670, 173, 20, 170, 6050.3, 4, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11976);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3915, 86, 64, 665, 85, 10, 85, 3025.15, 3, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11977);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3914, 86, 64, 677, 47, 6, 51, 1815.09, 2, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11978);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3913, 86, 64, 660, 23, 3, 25.5, 907.55, 1, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11979);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3912, 86, 64, 673, 112, 13, 110.5, 3932.7, 0, '2025-09-24 14:34:37', '2025-09-24 14:34:37', NULL, 1561, 11980);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3242, 87, 120, 1829, 75, 8, 75.2, 2932.8, 0, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11917);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3243, 87, 120, 1830, 72, 8, 75.2, 2932.8, 1, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11922);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3244, 87, 120, 1831, 65, 7, 65.8, 2566.2, 2, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11923);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3245, 87, 120, 1832, 36, 4, 37.6, 1466.4, 3, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11924);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3246, 87, 120, 1833, 38, 4, 37.6, 1466.4, 4, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11925);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3247, 87, 120, 1834, 55, 6, 56.4, 2199.6, 5, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11926);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3248, 87, 120, 1837, 103, 11, 103.4, 4032.6, 6, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11927);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3249, 87, 120, 1838, 37, 4, 37.6, 1466.4, 7, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11928);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3250, 87, 120, 1841, 48, 5, 47, 1833, 8, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11931);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3251, 87, 120, 1839, 78, 8, 75.2, 2932.8, 9, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11929);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3252, 87, 120, 1840, 90, 10, 94, 3666, 10, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11930);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3253, 87, 120, 1845, 75, 8, 75.2, 2932.8, 11, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11933);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3254, 87, 120, 1842, 40, 4, 37.6, 1466.4, 12, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11932);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3255, 87, 120, 1848, 92, 10, 94, 3666, 13, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11936);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3256, 87, 120, 1846, 52, 5, 47, 1833, 14, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11934);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3257, 87, 120, 1849, 52, 6, 56.4, 2199.6, 15, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11937);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3258, 87, 120, 1847, 22, 2, 18.8, 733.2, 16, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11935);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3259, 87, 120, 1850, 61, 7, 65.8, 2566.2, 17, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11938);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3260, 87, 120, 1851, 61, 7, 65.8, 2566.2, 18, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11954);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3261, 87, 120, 1854, 63, 7, 65.8, 2566.2, 19, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11955);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3262, 87, 120, 1856, 41, 4, 37.6, 1466.4, 20, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11956);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3263, 87, 120, 1857, 57, 6, 56.4, 2199.6, 21, '2025-09-15 20:12:52', '2025-09-15 20:12:52', NULL, 1557, 11957);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4560, 88, 48, 497, 159, 8, 152, 7733.76, 0, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12403);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4559, 88, 48, 521, 41, 2, 38, 1933.44, 1, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12409);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4558, 88, 48, 505, 123, 6, 114, 5800.32, 2, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12405);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4557, 88, 48, 517, 37, 2, 38, 1933.44, 3, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12406);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4556, 88, 48, 504, 78, 4, 76, 3866.88, 4, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12407);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4555, 88, 48, 500, 20, 1, 19, 966.72, 5, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12500);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4554, 88, 48, 519, 84, 4, 76, 3866.88, 6, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12418);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4553, 88, 48, 495, 54, 3, 57, 2900.16, 7, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12498);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4552, 88, 48, 520, 39, 2, 38, 1933.44, 8, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12497);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4551, 88, 48, 523, 22, 1, 19, 966.72, 9, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12499);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4550, 88, 48, 508, 38, 2, 38, 1933.44, 10, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12417);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4549, 88, 48, 515, 54, 3, 57, 2900.16, 11, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12420);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4548, 88, 48, 525, 108, 6, 114, 5800.32, 12, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12415);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4547, 88, 48, 512, 58, 3, 57, 2900.16, 13, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12416);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4546, 88, 48, 516, 25, 1, 19, 966.72, 14, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12414);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4545, 88, 48, 513, 28, 1, 19, 966.72, 15, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12413);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4544, 88, 48, 522, 78, 4, 76, 3866.88, 16, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12410);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4543, 88, 48, 499, 81, 4, 76, 3866.88, 17, '2025-10-06 19:22:49', '2025-10-10 19:27:26', NULL, 1607, 12408);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4604, 89, 114, 1276, 31.8, 2, 32, 1728, 0, '2025-10-09 11:07:08', '2025-10-28 16:20:37', NULL, 1537, 11747);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4603, 89, 114, 1280, 35.4, 2, 32, 1728, 1, '2025-10-09 11:07:08', '2025-10-28 16:20:37', NULL, 1537, 12111);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4602, 89, 114, 1283, 177, 11, 176, 9504, 2, '2025-10-09 11:07:08', '2025-10-28 16:20:37', NULL, 1537, 11739);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4601, 89, 114, 1284, 145.8, 9, 144, 7776, 3, '2025-10-09 11:07:08', '2025-10-28 16:20:37', NULL, 1537, 11740);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4600, 89, 114, 1278, 120, 8, 128, 6912, 4, '2025-10-09 11:07:08', '2025-10-28 16:20:37', NULL, 1537, 11738);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4599, 89, 114, 1275, 90, 6, 96, 5184, 5, '2025-10-09 11:07:08', '2025-10-28 16:20:37', NULL, 1537, 11745);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3724, 90, 65, 320, 43.2, 4, 40, 1423.6, 0, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11760);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3723, 90, 65, 345, 35.4, 4, 40, 1423.6, 1, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11761);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3722, 90, 65, 352, 38.4, 4, 40, 1423.6, 2, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11759);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3721, 90, 65, 1334, 54.6, 5, 50, 1779.5, 3, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11758);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3720, 90, 65, 361, 31.2, 3, 30, 1067.7, 4, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11757);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3719, 90, 65, 351, 63, 6, 60, 2135.4, 5, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11756);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3718, 90, 65, 1333, 46.8, 5, 50, 1779.5, 6, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11755);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3717, 90, 65, 355, 38.4, 4, 40, 1423.6, 7, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11754);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3716, 90, 65, 334, 37.2, 4, 40, 1423.6, 8, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11753);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3715, 90, 65, 1329, 17.4, 2, 20, 711.8, 9, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11752);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3714, 90, 65, 366, 43.2, 4, 40, 1423.6, 10, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11751);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3713, 90, 65, 330, 47.4, 5, 50, 1779.5, 11, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11749);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3712, 90, 65, 360, 30.6, 3, 30, 1067.7, 12, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11750);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3711, 90, 65, 347, 30.6, 3, 30, 1067.7, 13, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11762);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3710, 90, 65, 367, 32.4, 3, 30, 1067.7, 14, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11744);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3709, 90, 65, 358, 47.4, 5, 50, 1779.5, 15, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11715);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3708, 90, 65, 1267, 9.6, 1, 10, 355.9, 16, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11714);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3707, 90, 65, 326, 11.4, 1, 10, 355.9, 17, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11713);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3706, 90, 65, 340, 59.4, 6, 60, 2135.4, 18, '2025-09-19 17:42:45', '2025-10-29 12:35:33', NULL, 1532, 11746);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3864, 91, 129, 1086, 58.2, 7, 57.4, 2689.19, 0, '2025-09-22 15:27:06', '2025-10-29 12:06:32', NULL, 1604, 12399);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3863, 91, 129, 1082, 90, 11, 90.2, 4225.87, 1, '2025-09-22 15:27:06', '2025-10-29 12:06:32', NULL, 1604, 12395);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3862, 91, 129, 1792, 81, 10, 82, 3841.7, 2, '2025-09-22 15:27:06', '2025-10-29 12:06:32', NULL, 1604, 12398);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3861, 91, 129, 1085, 184.8, 23, 188.6, 8835.91, 3, '2025-09-22 15:27:06', '2025-10-29 12:06:32', NULL, 1604, 12396);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3860, 91, 129, 1384, 66, 8, 65.6, 3073.36, 4, '2025-09-22 15:27:06', '2025-10-29 12:06:32', NULL, 1604, 12397);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3732, 92, 128, 2379, 36, 3, 31.5, 1475.78, 0, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12667);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3731, 92, 128, 2375, 24, 2, 21, 983.85, 1, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12670);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3730, 92, 128, 1702, 41, 4, 42, 1967.7, 2, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12668);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3729, 92, 128, 1705, 72, 7, 73.5, 3443.47, 3, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12671);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3728, 92, 128, 2381, 19, 2, 21, 983.85, 4, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12666);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3727, 92, 128, 1707, 13, 1, 10.5, 491.93, 5, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12669);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3726, 92, 128, 1703, 125, 12, 126, 5903.1, 6, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12665);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3725, 92, 128, 1706, 29, 3, 31.5, 1475.78, 7, '2025-09-19 18:11:02', '2025-10-20 16:09:06', NULL, 1629, 12672);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3733, 93, 128, 274, 209, 20, 210, 9838.5, 0, '2025-09-19 18:11:38', '2025-10-20 16:07:20', NULL, 1545, 11842);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4754, 94, 132, 1018, 30, 3, 27, 852.93, 13, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12289);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4753, 94, 132, 1620, 66, 7, 63, 1990.17, 12, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12291);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4752, 94, 132, 1024, 49, 5, 45, 1421.55, 11, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12284);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4751, 94, 132, 1614, 51, 6, 54, 1705.86, 10, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12285);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4750, 94, 132, 1026, 33, 4, 36, 1137.24, 9, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12294);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4749, 94, 132, 1022, 54, 6, 54, 1705.86, 8, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12287);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4748, 94, 132, 1020, 44, 5, 45, 1421.55, 7, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12283);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4747, 94, 132, 1622, 36, 4, 36, 1137.24, 6, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12293);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4746, 94, 132, 1618, 54, 6, 54, 1705.86, 5, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12290);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4745, 94, 132, 1025, 36, 4, 36, 1137.24, 4, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12282);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4744, 94, 132, 1017, 30, 3, 27, 852.93, 3, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12295);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4743, 94, 132, 1019, 66, 7, 63, 1990.17, 2, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12288);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4742, 94, 132, 1021, 72, 8, 72, 2274.48, 1, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12292);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4741, 94, 132, 1023, 48, 5, 45, 1421.55, 0, '2025-10-09 18:11:04', '2025-10-09 18:11:04', NULL, 1593, 12286);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3524, 95, 82, 1912, 96, 6, 96, 3032.64, 0, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11690);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3525, 95, 82, 1905, 81, 5, 80, 2527.2, 1, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11673);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3526, 95, 82, 1909, 79, 5, 80, 2527.2, 2, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11678);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3527, 95, 82, 1896, 17, 1, 16, 505.44, 3, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11687);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3528, 95, 82, 1908, 45, 3, 48, 1516.32, 4, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11692);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3529, 95, 82, 1900, 72, 5, 80, 2527.2, 5, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11677);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3530, 95, 82, 1894, 82, 5, 80, 2527.2, 6, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11674);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3531, 95, 82, 1910, 71, 4, 64, 2021.76, 7, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11686);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3532, 95, 82, 1903, 39, 2, 32, 1010.88, 8, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11682);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3533, 95, 82, 1901, 75, 5, 80, 2527.2, 9, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11681);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3534, 95, 82, 2920, 36, 2, 32, 1010.88, 10, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11691);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3535, 95, 82, 1895, 98, 6, 96, 3032.64, 11, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11675);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3536, 95, 82, 1911, 73, 5, 80, 2527.2, 12, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11689);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3537, 95, 82, 1904, 34, 2, 32, 1010.88, 13, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11685);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3538, 95, 82, 1902, 66, 4, 64, 2021.76, 14, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11680);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3539, 95, 82, 1899, 66, 4, 64, 2021.76, 15, '2025-09-17 13:15:11', '2025-09-17 13:15:11', NULL, 1529, 11688);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4329, 96, 45, 2772, 52, 4, 54, 3567.78, 5, '2025-10-02 13:48:10', '2025-10-02 13:48:10', NULL, 1544, 11841);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4328, 96, 45, 2771, 54, 4, 54, 3567.78, 4, '2025-10-02 13:48:10', '2025-10-02 13:48:10', NULL, 1544, 11877);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4327, 96, 45, 1660, 127, 9, 121.5, 8027.5, 3, '2025-10-02 13:48:10', '2025-10-02 13:48:10', NULL, 1544, 11878);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4326, 96, 45, 4282, 60, 4, 54, 3567.78, 2, '2025-10-02 13:48:10', '2025-10-02 13:48:10', NULL, 1544, 12300);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4325, 96, 45, 2769, 95, 7, 94.5, 6243.61, 1, '2025-10-02 13:48:10', '2025-10-02 13:48:10', NULL, 1544, 11840);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4324, 96, 45, 2770, 62, 5, 67.5, 4459.72, 0, '2025-10-02 13:48:10', '2025-10-02 13:48:10', NULL, 1544, 11843);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4323, 97, 18, 564, 24, 2, 21.6, 624.46, 15, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11941);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4322, 97, 18, 574, 9, 1, 10.8, 312.23, 14, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11942);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4321, 97, 18, 578, 24, 2, 21.6, 624.46, 13, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11943);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4320, 97, 18, 568, 16, 1, 10.8, 312.23, 12, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11944);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4319, 97, 18, 582, 27, 3, 32.4, 936.68, 11, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11945);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4318, 97, 18, 571, 7, 1, 10.8, 312.23, 10, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11940);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4317, 97, 18, 560, 10, 1, 10.8, 312.23, 9, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11947);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4316, 97, 18, 573, 15, 1, 10.8, 312.23, 8, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11949);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4315, 97, 18, 565, 31, 3, 32.4, 936.68, 7, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11946);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4314, 97, 18, 563, 15, 1, 10.8, 312.23, 6, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11950);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4313, 97, 18, 2948, 13, 1, 10.8, 312.23, 5, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11952);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4312, 97, 18, 2949, 8, 1, 10.8, 312.23, 4, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11951);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4311, 97, 18, 570, 56, 5, 54, 1561.14, 3, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 12661);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4310, 97, 18, 2951, 9, 1, 10.8, 312.23, 2, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11953);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4309, 97, 18, 580, 11, 1, 10.8, 312.23, 1, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11948);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4308, 97, 18, 583, 13, 1, 10.8, 312.23, 0, '2025-10-02 13:27:47', '2025-10-02 13:27:47', NULL, 1558, 11939);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4178, 98, 104, 1726, 72, 8, 72, 3960, 0, '2025-09-29 19:59:49', '2025-10-28 13:45:31', NULL, 1581, 12180);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4177, 98, 104, 1056, 30, 3, 27, 1485, 1, '2025-09-29 19:59:49', '2025-10-28 13:45:31', NULL, 1581, 12176);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4176, 98, 104, 1058, 108, 12, 108, 5940, 2, '2025-09-29 19:59:49', '2025-10-28 13:45:31', NULL, 1581, 12178);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4175, 98, 104, 1059, 90, 10, 90, 4950, 3, '2025-09-29 19:59:49', '2025-10-28 13:45:31', NULL, 1581, 12179);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4174, 98, 104, 1727, 180, 20, 180, 9900, 4, '2025-09-29 19:59:49', '2025-10-28 13:45:31', NULL, 1581, 12172);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4173, 98, 104, 1055, 60, 7, 63, 3465, 5, '2025-09-29 19:59:49', '2025-10-28 13:45:31', NULL, 1581, 12177);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3818, 7, 19, 109, 15.6, 2, 16.6, 479.906, 1, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10396);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3819, 7, 19, 120, 3.6, 1, 8.3, 239.953, 2, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10394);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3820, 7, 19, 127, 24.6, 3, 24.9, 719.859, 3, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10404);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3821, 7, 19, 115, 67.8, 8, 66.4, 1919.624, 4, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10405);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3822, 7, 19, 125, 11.4, 1, 8.3, 239.953, 5, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10403);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3823, 7, 19, 119, 19.8, 2, 16.6, 479.906, 6, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10402);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3824, 7, 19, 105, 60.6, 7, 58.1, 1679.671, 7, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10400);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3825, 7, 19, 103, 107.4, 13, 107.9, 3119.389, 8, '2025-09-22 13:08:49', '2025-10-28 16:58:14', NULL, 1355, 10399);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3831, 99, 31, 2999, 15, 2, 20, 578.2, 0, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12273);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3832, 99, 31, 479, 210, 21, 210, 6071.1, 1, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12264);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3833, 99, 31, 472, 120, 12, 120, 3469.2, 2, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12263);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3834, 99, 31, 471, 18, 2, 20, 578.2, 3, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12261);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3835, 99, 31, 475, 24, 2, 20, 578.2, 4, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12262);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3836, 99, 31, 490, 33.6, 3, 30, 867.3, 5, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12272);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3837, 99, 31, 491, 9, 1, 10, 289.1, 6, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12265);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3838, 99, 31, 476, 36, 4, 40, 1156.4, 7, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12266);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3839, 99, 31, 488, 30, 3, 30, 867.3, 8, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12267);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3840, 99, 31, 467, 93.6, 9, 90, 2601.9, 9, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12268);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3841, 99, 31, 489, 90, 9, 90, 2601.9, 10, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12269);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3842, 99, 31, 492, 13.2, 1, 10, 289.1, 11, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12271);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3843, 99, 31, 480, 24, 2, 20, 578.2, 12, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12270);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(3844, 99, 31, 478, 15, 2, 20, 578.2, 13, '2025-09-22 13:22:30', '2025-10-28 16:37:12', NULL, 1589, 12274);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4506, 100, 114, 3742, 5, 1, 16, 556.96, 1, '2025-10-06 18:13:38', '2025-10-06 18:13:38', NULL, 1527, 12209);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4505, 100, 114, 4232, 34, 2, 32, 1113.92, 0, '2025-10-06 18:13:38', '2025-10-06 18:13:38', NULL, 1527, 11889);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4141, 101, 131, 2586, 30, 3, 30, 1526.4, 10, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12795);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4140, 101, 131, 2582, 60, 6, 60, 3052.8, 9, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12797);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4139, 101, 131, 4415, 6, 1, 10, 508.8, 8, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12838);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4138, 101, 131, 2575, 18, 2, 20, 1017.6, 7, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12798);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4137, 101, 131, 3776, 9, 1, 10, 508.8, 6, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12847);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4136, 101, 131, 2577, 30, 3, 30, 1526.4, 5, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12840);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4135, 101, 131, 2579, 24, 2, 20, 1017.6, 4, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12794);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4134, 101, 131, 4422, 30, 3, 30, 1526.4, 3, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12849);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4133, 101, 131, 2580, 45, 5, 50, 2544, 2, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12841);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4132, 101, 131, 2578, 15, 2, 20, 1017.6, 1, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12839);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4131, 101, 131, 2581, 9, 1, 10, 508.8, 0, '2025-09-29 18:46:13', '2025-09-29 18:46:13', NULL, 1643, 12796);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4294, 102, 103, 1689, 54, 5, 50, 2950, 3, '2025-10-01 14:30:05', '2025-10-01 14:30:05', NULL, 1613, 12801);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4293, 102, 103, 1697, 30, 3, 30, 1770, 2, '2025-10-01 14:30:05', '2025-10-01 14:30:05', NULL, 1613, 12478);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4292, 102, 103, 1692, 198, 20, 200, 11800, 1, '2025-10-01 14:30:05', '2025-10-01 14:30:05', NULL, 1613, 12480);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4291, 102, 103, 1695, 78, 8, 80, 4720, 0, '2025-10-01 14:30:05', '2025-10-01 14:30:05', NULL, 1613, 12479);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4184, 103, 48, 2236, 101, 5, 95, 4833.6, 0, '2025-09-30 12:30:23', '2025-10-17 17:28:04', NULL, 1555, 11895);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4183, 103, 48, 1073, 44, 2, 38, 1933.44, 1, '2025-09-30 12:30:23', '2025-10-17 17:28:04', NULL, 1555, 11891);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4182, 103, 48, 1070, 67, 4, 76, 3866.88, 2, '2025-09-30 12:30:23', '2025-10-17 17:28:04', NULL, 1555, 11890);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4181, 103, 48, 1361, 50, 3, 57, 2900.16, 3, '2025-09-30 12:30:23', '2025-10-17 17:28:04', NULL, 1555, 11893);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4180, 103, 48, 1365, 49, 3, 57, 2900.16, 4, '2025-09-30 12:30:23', '2025-10-17 17:28:04', NULL, 1555, 11892);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4179, 103, 48, 1359, 448, 24, 456, 23201.28, 5, '2025-09-30 12:30:23', '2025-10-17 17:28:04', NULL, 1555, 11894);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4740, 104, 116, 9, 19, 2, 18, 626.58, 0, '2025-10-09 16:27:58', '2025-10-17 12:43:58', NULL, 1658, 12941);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4739, 104, 116, 39, 45, 5, 45, 1566.45, 7, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12929);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4738, 104, 116, 37, 40, 4, 36, 1253.16, 1, '2025-10-09 16:27:58', '2025-10-17 12:43:58', NULL, 1658, 12933);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4737, 104, 116, 11, 90, 10, 90, 3132.9, 5, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12926);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4736, 104, 116, 22, 46, 5, 45, 1566.45, 4, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12938);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4735, 104, 116, 16, 41, 5, 45, 1566.45, 3, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12932);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4734, 104, 116, 21, 36, 4, 36, 1253.16, 2, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12928);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4733, 104, 116, 41, 36, 4, 36, 1253.16, 1, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12936);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4732, 104, 116, 12, 40, 4, 36, 1253.16, 0, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12937);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4731, 104, 114, 3037, 50, 3, 48, 1670.88, 10, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12945);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4730, 104, 114, 10, 124, 8, 128, 4455.68, 9, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12934);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4729, 104, 114, 32, 49, 3, 48, 1670.88, 8, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12930);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4728, 104, 114, 13, 68, 4, 64, 2227.84, 7, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12927);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4727, 104, 114, 27, 50, 3, 48, 1670.88, 6, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12944);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4726, 104, 114, 15, 52, 3, 48, 1670.88, 5, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12935);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4725, 104, 114, 8, 57, 4, 64, 2227.84, 4, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12940);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4724, 104, 114, 1263, 52, 3, 48, 1670.88, 3, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12939);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4723, 104, 114, 1425, 41, 3, 48, 1670.88, 2, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12931);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4722, 104, 114, 17, 34, 2, 32, 1113.92, 1, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12942);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4721, 104, 114, 14, 115, 7, 112, 3898.72, 0, '2025-10-09 16:27:58', '2025-10-15 14:58:35', '2025-10-15 14:58:35', 1658, 12943);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4038, 105, 81, 2238, 32, 3, 30, 1004.4, 0, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11808);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4039, 105, 81, 2245, 26, 3, 30, 1004.4, 1, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11806);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4040, 105, 81, 2240, 26, 3, 30, 1004.4, 2, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11805);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4041, 105, 81, 2248, 41, 4, 40, 1339.2, 3, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11804);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4042, 105, 81, 2256, 46, 5, 50, 1674, 4, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11809);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4043, 105, 81, 2249, 66, 7, 70, 2343.6, 5, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11799);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4044, 105, 81, 2253, 38, 4, 40, 1339.2, 6, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11800);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4045, 105, 81, 3993, 7, 1, 10, 334.8, 7, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11816);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4046, 105, 81, 2255, 20, 2, 20, 669.6, 8, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11801);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4047, 105, 81, 2257, 41, 4, 40, 1339.2, 9, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11807);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4048, 105, 81, 2254, 60, 6, 60, 2008.8, 10, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11802);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4049, 105, 81, 2246, 47, 5, 50, 1674, 11, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11803);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4050, 105, 81, 2242, 31, 3, 30, 1004.4, 12, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11810);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4051, 105, 81, 2247, 15, 2, 20, 669.6, 13, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11813);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4052, 105, 81, 2251, 35, 4, 40, 1339.2, 14, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11814);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4053, 105, 81, 2241, 49, 5, 50, 1674, 15, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11812);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4054, 105, 81, 2250, 18, 2, 20, 669.6, 16, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11811);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4055, 105, 81, 3994, 75, 8, 80, 2678.4, 17, '2025-09-29 11:02:02', '2025-09-29 11:02:02', NULL, 1540, 11815);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4056, 106, 20, 874, 52.8, 4, 56, 2744, 0, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12097);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4057, 106, 20, 884, 78, 6, 84, 4116, 1, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12094);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4058, 106, 20, 3706, 58.8, 4, 56, 2744, 2, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12109);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4059, 106, 20, 876, 55.8, 4, 56, 2744, 3, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12110);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4060, 106, 20, 3705, 39, 3, 42, 2058, 4, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12108);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4061, 106, 20, 889, 22.8, 2, 28, 1372, 5, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12107);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4062, 106, 20, 870, 27, 2, 28, 1372, 6, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12104);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4063, 106, 20, 869, 27, 2, 28, 1372, 7, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12106);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4064, 106, 20, 883, 69, 5, 70, 3430, 8, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12105);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4065, 106, 20, 867, 117, 8, 112, 5488, 9, '2025-09-29 11:14:06', '2025-10-30 14:12:30', NULL, 1572, 12103);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4066, 106, 20, 888, 72.6, 5, 70, 3430, 10, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12102);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4067, 106, 20, 892, 24, 2, 28, 1372, 11, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12101);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4068, 106, 20, 882, 49.2, 4, 56, 2744, 12, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12089);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4069, 106, 20, 894, 61.2, 4, 56, 2744, 13, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12090);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4070, 106, 20, 895, 39, 3, 42, 2058, 14, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12092);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4071, 106, 20, 877, 54, 4, 56, 2744, 15, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12091);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4072, 106, 20, 897, 114, 8, 112, 5488, 16, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12093);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4073, 106, 20, 872, 21, 2, 28, 1372, 17, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12096);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4074, 106, 20, 868, 21.6, 2, 28, 1372, 18, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12095);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4075, 106, 20, 878, 58.2, 4, 56, 2744, 19, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12098);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4076, 106, 20, 893, 51.6, 4, 56, 2744, 20, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12099);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4077, 106, 20, 880, 28.2, 2, 28, 1372, 21, '2025-09-29 11:14:06', '2025-10-30 14:12:31', NULL, 1572, 12100);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4078, 107, 57, 591, 25.8, 2, 24, 1248, 0, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11521);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4079, 107, 57, 585, 71.4, 6, 72, 3744, 1, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11514);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4080, 107, 57, 587, 54, 5, 60, 3120, 2, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11515);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4081, 107, 57, 588, 117.6, 10, 120, 6240, 3, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11510);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4082, 107, 57, 589, 23.4, 2, 24, 1248, 4, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11511);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4083, 107, 57, 596, 89.4, 7, 84, 4368, 5, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11513);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4084, 107, 57, 590, 9.6, 1, 12, 624, 6, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11512);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4085, 107, 57, 586, 27, 2, 24, 1248, 7, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11509);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4086, 107, 57, 1302, 22.2, 2, 24, 1248, 8, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11516);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4087, 107, 57, 1300, 11.4, 1, 12, 624, 9, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11517);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4088, 107, 57, 1298, 52.8, 4, 48, 2496, 10, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11518);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4089, 107, 57, 595, 8.4, 1, 12, 624, 11, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11519);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4090, 107, 57, 597, 100.8, 8, 96, 4992, 12, '2025-09-29 11:24:35', '2025-10-30 14:26:06', NULL, 1317, 11520);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4115, 108, 108, 247, 58, 6, 61.2, 2786.44, 3, '2025-09-29 14:36:46', '2025-09-29 14:36:46', NULL, 1573, 12122);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4114, 108, 108, 262, 73, 7, 71.4, 3250.84, 2, '2025-09-29 14:36:46', '2025-09-29 14:36:46', NULL, 1573, 12112);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4113, 108, 108, 251, 22, 2, 20.4, 928.81, 1, '2025-09-29 14:36:46', '2025-09-29 14:36:46', NULL, 1573, 12113);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4112, 108, 108, 3703, 58, 6, 61.2, 2786.44, 0, '2025-09-29 14:36:46', '2025-09-29 14:36:46', NULL, 1573, 12114);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4229, 109, 69, 1419, 27, 2, 29, 1073, 0, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12000);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4228, 109, 69, 1417, 31.8, 2, 29, 1073, 1, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12003);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4227, 109, 69, 3767, 48, 3, 43.5, 1609.5, 2, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12006);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4226, 109, 69, 1411, 24, 2, 29, 1073, 3, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12005);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4225, 109, 69, 3769, 48, 3, 43.5, 1609.5, 4, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12002);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4224, 109, 69, 1011, 27, 2, 29, 1073, 5, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12004);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4223, 109, 69, 3768, 60, 4, 58, 2146, 6, '2025-09-30 14:38:47', '2025-10-29 14:57:27', NULL, 1564, 12001);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4241, 110, 3, 608, 24, 2, 20, 1120, 0, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12641);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4240, 110, 3, 626, 18, 2, 20, 1120, 1, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12644);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4239, 110, 3, 604, 15.6, 2, 20, 1120, 2, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12643);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4238, 110, 3, 3736, 74.4, 7, 70, 3920, 3, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12793);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4237, 110, 3, 4377, 30, 3, 30, 1680, 4, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12649);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4236, 110, 3, 620, 27, 3, 30, 1680, 5, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12647);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4235, 110, 3, 617, 18, 2, 20, 1120, 6, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12646);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4234, 110, 3, 610, 24, 2, 20, 1120, 7, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12645);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4233, 110, 3, 605, 37.8, 4, 40, 2240, 8, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12642);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4232, 110, 3, 606, 12, 1, 10, 560, 9, '2025-09-30 16:00:24', '2025-10-30 14:36:12', NULL, 1626, 12640);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4231, 111, 6, 550, 288, 34, 287.3, 11543.714, 0, '2025-09-30 15:59:11', '2025-10-30 14:39:41', NULL, 1627, 12660);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4286, 112, 71, 1262, 39, 2, 35.2, 1302.4, 0, '2025-10-01 14:17:23', '2025-10-29 14:58:07', NULL, 1492, 12648);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4285, 112, 71, 1257, 240, 14, 246.4, 9116.8, 1, '2025-10-01 14:17:23', '2025-10-29 14:58:07', NULL, 1492, 12638);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4284, 112, 71, 1261, 102, 6, 105.6, 3907.2, 2, '2025-10-01 14:17:23', '2025-10-29 14:58:07', NULL, 1492, 12637);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4283, 112, 71, 1259, 63, 4, 70.4, 2604.8, 3, '2025-10-01 14:17:23', '2025-10-29 14:58:07', NULL, 1492, 12635);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4282, 112, 71, 1260, 120, 7, 123.2, 4558.4, 4, '2025-10-01 14:17:23', '2025-10-29 14:58:07', NULL, 1492, 12636);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4281, 112, 71, 828, 38.4, 2, 35.2, 1302.4, 5, '2025-10-01 14:17:23', '2025-10-29 14:58:07', NULL, 1492, 12639);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4701, 113, 112, 423, 63, 4, 60, 2275.8, 0, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12196);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4702, 113, 112, 432, 23.4, 2, 30, 1137.9, 1, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12200);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4703, 113, 112, 442, 14.4, 1, 15, 568.95, 2, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12194);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4704, 113, 112, 425, 47.4, 3, 45, 1706.85, 3, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12198);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4705, 113, 112, 463, 55.2, 4, 60, 2275.8, 4, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12185);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4706, 113, 112, 458, 53.4, 4, 60, 2275.8, 5, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12199);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4707, 113, 112, 464, 78, 5, 75, 2844.75, 6, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12197);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4708, 113, 112, 444, 42.6, 3, 45, 1706.85, 7, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12186);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4709, 113, 112, 443, 277.8, 19, 285, 10810.05, 8, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12192);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4710, 113, 112, 448, 72, 5, 75, 2844.75, 9, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12187);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4711, 113, 112, 459, 90, 6, 90, 3413.7, 10, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12188);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4712, 113, 112, 466, 105, 7, 105, 3982.65, 11, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12189);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4713, 113, 112, 1116, 10.8, 1, 15, 568.95, 12, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12191);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4714, 113, 112, 457, 61.2, 4, 60, 2275.8, 13, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12203);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4715, 113, 112, 422, 13.2, 1, 15, 568.95, 14, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12193);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4716, 113, 112, 440, 11.4, 1, 15, 568.95, 15, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12190);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4717, 113, 112, 462, 30, 2, 30, 1137.9, 16, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12201);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4718, 113, 112, 429, 14.4, 1, 15, 568.95, 17, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12202);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4719, 113, 112, 434, 15, 1, 15, 568.95, 18, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12204);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4720, 113, 112, 445, 45.6, 3, 45, 1706.85, 19, '2025-10-09 13:50:57', '2025-10-29 14:43:13', NULL, 1582, 12195);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4755, 104, 107, 1425, 41, 4, 44, 1531.64, 0, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12931);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4756, 104, 107, 12, 40, 4, 44, 1531.64, 1, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12937);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4757, 104, 107, 32, 49, 4, 44, 1531.64, 2, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12930);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4758, 104, 107, 41, 36, 3, 33, 1148.73, 3, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12936);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4759, 104, 107, 11, 90, 8, 88, 3063.28, 4, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12926);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4760, 104, 107, 13, 68, 6, 66, 2297.46, 5, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12927);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4761, 104, 107, 21, 36, 3, 33, 1148.73, 6, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12928);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4762, 104, 107, 16, 41, 4, 44, 1531.64, 7, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12932);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4763, 104, 107, 39, 45, 4, 44, 1531.64, 8, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12929);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4764, 104, 107, 22, 46, 4, 44, 1531.64, 9, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12938);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4765, 104, 107, 1263, 52, 5, 55, 1914.55, 10, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12939);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4766, 104, 116, 15, 52, 6, 54, 1879.74, 2, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12935);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4767, 104, 116, 14, 115, 13, 117, 4072.77, 3, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12943);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4768, 104, 116, 8, 57, 6, 54, 1879.74, 4, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12940);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4769, 104, 116, 17, 34, 4, 36, 1253.16, 5, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12942);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4770, 104, 116, 27, 50, 6, 54, 1879.74, 6, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12944);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4771, 104, 116, 10, 124, 14, 126, 4386.06, 7, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12934);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4772, 104, 116, 3037, 50, 6, 54, 1879.74, 8, '2025-10-15 14:58:35', '2025-10-17 12:43:58', NULL, 1658, 12945);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4773, 114, 7, 4294, 96, 13, 97.5, 5265, 0, '2025-10-16 12:28:55', '2025-10-22 12:37:29', '2025-10-22 12:37:29', 1453, 12302);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4774, 114, 8, 4302, 177, 21, 174.3, 9412.2, 0, '2025-10-16 12:28:55', '2025-10-31 12:37:43', NULL, 1453, 12304);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4775, 114, 9, 4314, 78, 11, 77, 4158, 0, '2025-10-16 12:28:55', '2025-10-22 12:37:29', '2025-10-22 12:37:29', 1453, 12571);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4776, 114, 10, 4315, 84, 12, 87.6, 4730.4, 0, '2025-10-16 12:28:55', '2025-10-22 12:37:29', '2025-10-22 12:37:29', 1453, 12590);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4777, 114, 11, 4296, 150, 11, 148.5, 8019, 0, '2025-10-16 12:28:55', '2025-10-22 12:37:29', '2025-10-22 12:37:29', 1453, 12617);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4778, 114, 12, 4299, 120, 8, 116, 6264, 0, '2025-10-16 12:28:55', '2025-10-22 12:37:29', '2025-10-22 12:37:29', 1453, 12618);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4779, 114, 13, 4313, 114, 16, 112, 6048, 0, '2025-10-16 12:28:55', '2025-10-22 12:37:29', '2025-10-22 12:37:29', 1453, 12631);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4780, 115, 14, 2989, 10, 1, 10, 378.3, 0, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12880);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4781, 115, 14, 1030, 43, 4, 40, 1513.2, 1, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12881);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4782, 115, 14, 1043, 89, 9, 90, 3404.7, 2, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12882);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4783, 115, 14, 1049, 21, 2, 20, 756.6, 3, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12883);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4784, 115, 15, 2983, 34, 3, 29.4, 1112.2, 0, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12884);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4785, 115, 15, 1052, 4, 1, 9.8, 370.73, 1, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12885);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4786, 115, 15, 1028, 25, 3, 29.4, 1112.2, 2, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12886);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4787, 115, 15, 1045, 40, 4, 39.2, 1482.94, 3, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12887);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4788, 115, 16, 1031, 11.04, 1, 9.5, 359.385, 0, '2025-10-16 12:35:26', '2025-10-30 17:19:13', NULL, 1644, 12888);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4789, 115, 16, 1035, 6.21, 1, 9.5, 359.385, 1, '2025-10-16 12:35:26', '2025-10-30 17:19:13', NULL, 1644, 12889);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4790, 115, 16, 1034, 127.65, 13, 123.5, 4672.005, 2, '2025-10-16 12:35:26', '2025-10-30 17:19:13', NULL, 1644, 12890);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4791, 115, 16, 1037, 51.75, 5, 47.5, 1796.925, 3, '2025-10-16 12:35:26', '2025-10-30 17:19:13', NULL, 1644, 12891);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4792, 115, 24, 1044, 4, 1, 14.5, 548.53, 0, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12892);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4793, 115, 24, 1047, 43, 3, 43.5, 1645.61, 1, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12893);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4794, 115, 24, 1039, 17, 1, 14.5, 548.53, 2, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12894);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4795, 115, 24, 1032, 61, 4, 58, 2194.14, 3, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12895);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4796, 115, 34, 1033, 21, 3, 24, 907.92, 0, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12896);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4797, 115, 34, 1036, 5, 1, 8, 302.64, 1, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12897);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4798, 115, 34, 1679, 18, 2, 16, 605.28, 2, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12898);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4799, 115, 34, 2996, 94, 12, 96, 3631.68, 3, '2025-10-16 12:35:26', '2025-10-22 12:47:32', '2025-10-22 12:47:32', 1644, 12899);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4800, 16, 59, 2503, 99, 10, 100, 6000, 0, '2025-10-16 13:17:52', '2025-10-16 15:33:04', NULL, 1570, 12080);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4801, 16, 59, 2504, 64, 6, 60, 3600, 1, '2025-10-16 13:17:52', '2025-10-16 15:33:04', NULL, 1570, 12083);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4802, 16, 59, 2512, 59, 6, 60, 3600, 2, '2025-10-16 13:17:52', '2025-10-16 15:33:04', NULL, 1570, 12081);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4803, 16, 59, 2489, 52, 5, 50, 3000, 3, '2025-10-16 13:17:52', '2025-10-16 15:33:04', NULL, 1570, 12082);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4804, 16, 59, 3981, 74, 7, 70, 4200, 4, '2025-10-16 13:17:52', '2025-10-16 15:33:04', NULL, 1570, 12084);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4805, 114, 8, 4314, 78, 9, 74.7, 4033.8, 1, '2025-10-22 12:37:29', '2025-10-31 12:37:43', NULL, 1453, 12571);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4806, 114, 8, 4296, 150, 18, 149.4, 8067.6, 2, '2025-10-22 12:37:29', '2025-10-31 12:37:43', NULL, 1453, 12617);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4807, 114, 8, 4313, 114, 14, 116.2, 6274.8, 3, '2025-10-22 12:37:29', '2025-10-31 12:37:43', NULL, 1453, 12631);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4808, 114, 8, 4315, 84, 10, 83, 4482, 4, '2025-10-22 12:37:29', '2025-10-31 12:37:43', NULL, 1453, 12590);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4809, 114, 8, 4299, 120, 14, 116.2, 6274.8, 5, '2025-10-22 12:37:29', '2025-10-31 12:37:43', NULL, 1453, 12618);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4810, 114, 8, 4294, 96, 12, 99.6, 5378.4, 6, '2025-10-22 12:37:29', '2025-10-31 12:37:43', NULL, 1453, 12302);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4811, 115, 16, 2983, 19.32, 2, 19, 718.77, 4, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12884);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4812, 115, 16, 1679, 10.35, 1, 9.5, 359.385, 5, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12898);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4813, 115, 16, 2989, 5.87, 1, 9.5, 359.385, 6, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12880);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4814, 115, 16, 2996, 54.16, 6, 57, 2156.31, 7, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12899);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4815, 115, 16, 1030, 24.5, 3, 28.5, 1078.155, 8, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12881);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4816, 115, 16, 1036, 3.1, 1, 9.5, 359.385, 9, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12897);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4817, 115, 16, 1033, 12.07, 1, 9.5, 359.385, 10, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12896);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4818, 115, 16, 1032, 34.84, 4, 38, 1437.54, 11, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12895);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4819, 115, 16, 1039, 9.66, 1, 9.5, 359.385, 12, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12894);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4820, 115, 16, 1047, 24.84, 3, 28.5, 1078.155, 13, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12893);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4821, 115, 16, 1044, 2.42, 1, 9.5, 359.385, 14, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12892);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4822, 115, 16, 1052, 2.07, 1, 9.5, 359.385, 15, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12885);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4823, 115, 16, 1043, 51.06, 5, 47.5, 1796.925, 16, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12882);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4824, 115, 16, 1049, 12.07, 1, 9.5, 359.385, 17, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12883);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4825, 115, 16, 1028, 14.49, 2, 19, 718.77, 18, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12886);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4826, 115, 16, 1045, 22.77, 2, 19, 718.77, 19, '2025-10-22 12:47:32', '2025-10-30 17:19:13', NULL, 1644, 12887);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4827, 116, 32, 2040, 162, 12, 158.4, 8236.8, 0, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12580);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4828, 116, 32, 2306, 46.2, 4, 52.8, 2745.6, 1, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12579);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4829, 116, 32, 2045, 47.4, 4, 52.8, 2745.6, 2, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12576);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4830, 116, 32, 2055, 28.2, 2, 26.4, 1372.8, 3, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12575);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4831, 116, 32, 2059, 72.6, 6, 79.2, 4118.4, 4, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12574);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4832, 116, 32, 2048, 49.2, 4, 52.8, 2745.6, 5, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12572);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4833, 116, 32, 2043, 22.8, 2, 26.4, 1372.8, 6, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12578);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4834, 116, 32, 2046, 60, 5, 66, 3432, 7, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12567);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4835, 116, 32, 2060, 47.4, 4, 52.8, 2745.6, 8, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12556);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4836, 116, 32, 2307, 27, 2, 26.4, 1372.8, 9, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12552);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4837, 116, 32, 2053, 114, 9, 118.8, 6177.6, 10, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12568);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4838, 116, 32, 2042, 34.2, 3, 39.6, 2059.2, 11, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12550);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4839, 116, 32, 2305, 47.4, 4, 52.8, 2745.6, 12, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12551);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4840, 116, 32, 2050, 33, 3, 39.6, 2059.2, 13, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12547);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4841, 116, 32, 2062, 33.6, 3, 39.6, 2059.2, 14, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12524);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4842, 116, 32, 2054, 30, 2, 26.4, 1372.8, 15, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12545);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4843, 116, 32, 2041, 69.6, 5, 66, 3432, 16, '2025-10-24 12:04:09', '2025-10-30 12:50:41', NULL, 1617, 12577);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4844, 117, 27, 2029, 36, 3, 34.5, 1794, 0, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12483);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4845, 117, 27, 2038, 24, 2, 23, 1196, 1, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12484);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4846, 117, 27, 4349, 24, 2, 23, 1196, 2, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12503);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4847, 117, 27, 4350, 24, 2, 23, 1196, 3, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12502);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4848, 117, 27, 2037, 24, 2, 23, 1196, 4, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12501);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4849, 117, 27, 2222, 60, 5, 57.5, 2990, 5, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12486);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4850, 117, 27, 2024, 45, 4, 46, 2392, 6, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12494);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4851, 117, 27, 2014, 54, 5, 57.5, 2990, 7, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12495);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4852, 117, 27, 2021, 54, 5, 57.5, 2990, 8, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12489);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4853, 117, 27, 2011, 120, 10, 115, 5980, 9, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12488);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4854, 117, 27, 2023, 60, 5, 57.5, 2990, 10, '2025-10-24 12:06:08', '2025-10-30 12:29:06', NULL, 1614, 12485);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4855, 118, 29, 2091, 22.8, 2, 20, 1040, 0, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12394);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4856, 118, 29, 3970, 57, 6, 60, 3120, 1, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12385);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4857, 118, 29, 2085, 51, 5, 50, 2600, 2, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12384);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4858, 118, 29, 3962, 56.4, 6, 60, 3120, 3, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12383);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4859, 118, 29, 3853, 81.6, 8, 80, 4160, 4, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12381);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4860, 118, 29, 2080, 63, 6, 60, 3120, 5, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12382);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4861, 118, 28, 2081, 73.2, 8, 72.8, 3785.6, 0, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12380);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4862, 118, 28, 2087, 120, 13, 118.3, 6151.6, 1, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12387);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4863, 118, 28, 2089, 54, 6, 54.6, 2839.2, 2, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12386);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4864, 118, 28, 3887, 47.4, 5, 45.5, 2366, 3, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12388);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4865, 118, 28, 2084, 57.6, 6, 54.6, 2839.2, 4, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12379);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4866, 118, 28, 2082, 72, 8, 72.8, 3785.6, 5, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12378);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4867, 118, 28, 2083, 67.2, 7, 63.7, 3312.4, 6, '2025-10-24 12:07:35', '2025-10-30 13:04:46', NULL, 1600, 12377);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4868, 119, 72, 1109, 55.2, 3, 49.5, 1831.5, 0, '2025-10-24 12:11:50', '2025-10-30 13:27:08', NULL, 1403, 11499);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4869, 119, 72, 1106, 84, 5, 82.5, 3052.5, 1, '2025-10-24 12:11:50', '2025-10-30 13:27:08', NULL, 1403, 11497);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4870, 119, 72, 1111, 87, 5, 82.5, 3052.5, 2, '2025-10-24 12:11:50', '2025-10-30 13:27:08', NULL, 1403, 11496);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4871, 119, 72, 1112, 63, 4, 66, 2442, 3, '2025-10-24 12:11:50', '2025-10-30 13:27:08', NULL, 1403, 11495);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4872, 119, 72, 1113, 457.8, 28, 462, 17094, 4, '2025-10-24 12:11:50', '2025-10-30 13:27:08', NULL, 1403, 11498);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4873, 119, 72, 1104, 147, 9, 148.5, 5494.5, 5, '2025-10-24 12:11:50', '2025-10-30 13:27:08', NULL, 1403, 11501);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4874, 120, 93, 3356, 18.6, 2, 21, 819, 0, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12049);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4875, 120, 93, 3376, 9, 1, 10.5, 409.5, 1, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12048);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4876, 120, 93, 3353, 12, 1, 10.5, 409.5, 2, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12047);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4877, 120, 93, 3394, 24.6, 2, 21, 819, 3, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12046);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4878, 120, 93, 3386, 19.2, 2, 21, 819, 4, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12045);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4879, 120, 93, 3335, 33, 3, 31.5, 1228.5, 5, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12053);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4880, 120, 93, 3326, 7.8, 1, 10.5, 409.5, 6, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12052);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4881, 120, 93, 3384, 8.4, 1, 10.5, 409.5, 7, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12054);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4882, 120, 93, 3371, 33.6, 3, 31.5, 1228.5, 8, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12051);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4883, 120, 93, 3339, 21, 2, 21, 819, 9, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12050);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4884, 120, 93, 3375, 16.8, 2, 21, 819, 10, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12055);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4885, 120, 93, 3349, 6.6, 1, 10.5, 409.5, 11, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12056);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4886, 120, 93, 3355, 15, 1, 10.5, 409.5, 12, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12057);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4887, 120, 93, 3333, 36, 3, 31.5, 1228.5, 13, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12058);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4888, 120, 93, 3399, 45.6, 4, 42, 1638, 14, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12059);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4889, 120, 93, 3390, 31.8, 3, 31.5, 1228.5, 15, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12044);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4890, 120, 93, 3346, 6, 1, 10.5, 409.5, 16, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12060);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4891, 120, 93, 3370, 7.2, 1, 10.5, 409.5, 17, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12043);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4892, 120, 93, 3520, 58.8, 6, 63, 2457, 18, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12036);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4893, 120, 93, 3372, 21.6, 2, 21, 819, 19, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12032);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4894, 120, 93, 3345, 30, 3, 31.5, 1228.5, 20, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12037);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4895, 120, 93, 3373, 42, 4, 42, 1638, 21, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12033);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4896, 120, 93, 3332, 18, 2, 21, 819, 22, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12038);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4897, 120, 93, 3374, 5.4, 1, 10.5, 409.5, 23, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12039);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4898, 120, 93, 3383, 206.4, 20, 210, 8190, 24, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12040);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4899, 120, 92, 3360, 10.8, 1, 11, 429, 0, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12042);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4900, 120, 92, 3336, 10.2, 1, 11, 429, 1, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12041);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4901, 120, 92, 3389, 54, 5, 55, 2145, 2, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12035);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4902, 120, 92, 3363, 25.2, 2, 22, 858, 3, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12030);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4903, 120, 92, 3393, 13.8, 1, 11, 429, 4, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12029);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4904, 120, 92, 3378, 14.4, 1, 11, 429, 5, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12031);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4905, 120, 92, 3334, 18.6, 2, 22, 858, 6, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12027);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4906, 120, 92, 3377, 34.2, 3, 33, 1287, 7, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12028);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4907, 120, 92, 3320, 9, 1, 11, 429, 8, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12025);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4908, 120, 92, 3382, 18.6, 2, 22, 858, 9, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12026);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4909, 120, 92, 3357, 33.6, 3, 33, 1287, 10, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12023);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4910, 120, 92, 3358, 23.4, 2, 22, 858, 11, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12024);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4911, 120, 92, 3325, 17.4, 2, 22, 858, 12, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12022);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4912, 120, 92, 3331, 22.8, 2, 22, 858, 13, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12020);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4913, 120, 92, 3387, 43.8, 4, 44, 1716, 14, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12021);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4914, 120, 92, 3327, 7.8, 1, 11, 429, 15, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12019);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4915, 120, 92, 3338, 27, 2, 22, 858, 16, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12018);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4916, 120, 92, 3341, 13.2, 1, 11, 429, 17, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12015);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4917, 120, 92, 3362, 25.2, 2, 22, 858, 18, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12016);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4918, 120, 92, 3343, 34.2, 3, 33, 1287, 19, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12017);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4919, 120, 92, 3321, 19.2, 2, 22, 858, 20, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12034);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4920, 120, 92, 3366, 84, 8, 88, 3432, 21, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12012);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4921, 120, 92, 3388, 6.6, 1, 11, 429, 22, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12013);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4922, 120, 92, 3368, 40.8, 4, 44, 1716, 23, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12011);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4923, 120, 92, 3369, 19.2, 2, 22, 858, 24, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12010);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4924, 120, 92, 3319, 7.8, 1, 11, 429, 25, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12008);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4925, 120, 92, 3385, 15.6, 1, 11, 429, 26, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12009);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4926, 120, 92, 3324, 54, 5, 55, 2145, 27, '2025-10-30 14:41:54', '2025-10-30 14:52:21', NULL, 1566, 12014);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4927, 121, 88, 1351, 46.8, 4, 44, 1320, 0, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12620);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4928, 121, 88, 1519, 21, 2, 22, 660, 1, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12622);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4929, 121, 88, 1343, 34.8, 3, 33, 990, 2, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12623);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4930, 121, 88, 1518, 30, 3, 33, 990, 3, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12624);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4931, 121, 88, 698, 63, 6, 66, 1980, 4, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12629);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4932, 121, 88, 691, 54, 5, 55, 1650, 5, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12628);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4933, 121, 88, 700, 34.8, 3, 33, 990, 6, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12627);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4934, 121, 88, 696, 61.2, 6, 66, 1980, 7, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12625);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4935, 121, 88, 692, 21, 2, 22, 660, 8, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12626);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4936, 121, 88, 689, 91.8, 8, 88, 2640, 9, '2025-10-30 14:53:33', '2025-10-30 14:59:13', NULL, 1625, 12621);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4937, 122, 89, 1002, 27, 3, 26.1, 783, 0, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11152);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4938, 122, 89, 999, 28.2, 3, 26.1, 783, 1, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11151);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4939, 122, 89, 2798, 18, 2, 17.4, 522, 2, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11156);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4940, 122, 89, 1004, 18, 2, 17.4, 522, 3, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11161);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4941, 122, 89, 1003, 45, 5, 43.5, 1305, 4, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11181);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4942, 122, 89, 988, 12, 1, 8.7, 261, 5, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11162);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4943, 122, 89, 995, 50.4, 6, 52.2, 1566, 6, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11160);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4944, 122, 89, 1377, 27, 3, 26.1, 783, 7, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11159);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4945, 122, 89, 992, 15, 2, 17.4, 522, 8, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11158);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4946, 122, 89, 989, 27, 3, 26.1, 783, 9, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11157);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4947, 122, 89, 1372, 57.6, 7, 60.9, 1827, 10, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11150);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4948, 122, 89, 1378, 24, 3, 26.1, 783, 11, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11155);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4949, 122, 89, 997, 21, 2, 17.4, 522, 12, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11154);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4950, 122, 89, 993, 15.6, 2, 17.4, 522, 13, '2025-10-30 16:14:55', '2025-10-30 16:24:45', NULL, 1401, 11153);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4951, 123, 117, 1800, 18, 2, 20, 1120, 0, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12516);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4952, 123, 117, 1795, 45, 5, 50, 2800, 1, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12513);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4953, 123, 117, 3956, 18, 2, 20, 1120, 2, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12521);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4954, 123, 117, 1798, 48, 5, 50, 2800, 3, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12512);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4955, 123, 117, 1794, 108, 11, 110, 6160, 4, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12519);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4956, 123, 117, 1799, 48, 5, 50, 2800, 5, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12517);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4957, 123, 117, 3958, 22.2, 2, 20, 1120, 6, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12523);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4958, 123, 117, 1796, 36, 4, 40, 2240, 7, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12515);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4959, 123, 117, 3957, 10.8, 1, 10, 560, 8, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12522);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4960, 123, 117, 1797, 38.4, 4, 40, 2240, 9, '2025-10-30 16:36:37', '2025-10-30 16:38:10', NULL, 1615, 12511);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4961, 124, 83, 960, 300, 19, 304, 9603.36, 0, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11992);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4962, 124, 83, 962, 81, 5, 80, 2527.2, 1, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11996);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4963, 124, 83, 963, 32.4, 2, 32, 1010.88, 2, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11991);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4964, 124, 83, 970, 27, 2, 32, 1010.88, 3, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11995);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4965, 124, 83, 967, 27, 2, 32, 1010.88, 4, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11994);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4966, 124, 83, 972, 180, 11, 176, 5559.84, 5, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11993);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4967, 124, 83, 959, 80.4, 5, 80, 2527.2, 6, '2025-10-30 16:40:11', '2025-10-30 16:42:38', NULL, 1562, 11997);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4968, 125, 130, 4005, 55.8, 5, 53, 2981.25, 0, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11668);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4969, 125, 130, 318, 52.2, 5, 53, 2981.25, 1, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11663);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4970, 125, 130, 307, 97.2, 9, 95.4, 5366.25, 2, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11665);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4971, 125, 130, 315, 49.2, 5, 53, 2981.25, 3, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11664);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4972, 125, 130, 309, 63, 6, 63.6, 3577.5, 4, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11667);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4973, 125, 130, 308, 61.2, 6, 63.6, 3577.5, 5, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11666);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4974, 125, 130, 4007, 43.2, 4, 42.4, 2385, 6, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11670);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4975, 125, 130, 314, 49.2, 5, 53, 2981.25, 7, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11662);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4976, 125, 130, 316, 61.2, 6, 63.6, 3577.5, 8, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11661);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4977, 125, 130, 311, 52.8, 5, 53, 2981.25, 9, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11659);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4978, 125, 130, 306, 85.2, 8, 84.8, 4770, 10, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11660);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4979, 125, 130, 4006, 61.8, 6, 63.6, 3577.5, 11, '2025-10-30 16:51:27', '2025-10-30 16:53:39', NULL, 1528, 11669);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4980, 126, 120, 1857, 57, 5, 52.5, 2047.5, 0, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11957);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4981, 126, 120, 1856, 40.8, 4, 42, 1638, 1, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11956);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4982, 126, 120, 1854, 63, 6, 63, 2457, 2, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11955);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4983, 126, 120, 1851, 61.2, 6, 63, 2457, 3, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11954);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4984, 126, 120, 1850, 61.2, 6, 63, 2457, 4, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11938);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4985, 126, 120, 1847, 21.6, 2, 21, 819, 5, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11935);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4986, 126, 120, 1849, 52.2, 5, 52.5, 2047.5, 6, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11937);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4987, 126, 120, 1846, 51.6, 5, 52.5, 2047.5, 7, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11934);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4988, 126, 120, 1848, 91.8, 9, 94.5, 3685.5, 8, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11936);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4989, 126, 120, 1842, 40.2, 4, 42, 1638, 9, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11932);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4990, 126, 120, 1845, 75, 7, 73.5, 2866.5, 10, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11933);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4991, 126, 120, 1840, 90, 9, 94.5, 3685.5, 11, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11930);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4992, 126, 120, 1839, 78, 7, 73.5, 2866.5, 12, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11929);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4993, 126, 120, 1841, 48, 5, 52.5, 2047.5, 13, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11931);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4994, 126, 120, 1838, 37.2, 4, 42, 1638, 14, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11928);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4995, 126, 120, 1837, 103.2, 10, 105, 4095, 15, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11927);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4996, 126, 120, 1834, 55.2, 5, 52.5, 2047.5, 16, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11926);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4997, 126, 120, 1833, 38.4, 4, 42, 1638, 17, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11925);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4998, 126, 120, 1832, 36, 3, 31.5, 1228.5, 18, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11924);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(4999, 126, 120, 1831, 64.8, 6, 63, 2457, 19, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11923);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5000, 126, 120, 1830, 72, 7, 73.5, 2866.5, 20, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11922);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5001, 126, 120, 1829, 75, 7, 73.5, 2866.5, 21, '2025-10-30 16:56:42', '2025-10-30 16:57:26', NULL, 1557, 11917);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5002, 127, 62, 4372, 305.4, 10, 300, 11379, 0, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12658);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5003, 127, 62, 4371, 96.6, 3, 90, 3413.7, 1, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12657);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5004, 127, 62, 4370, 422.4, 14, 420, 15930.6, 2, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12656);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5005, 127, 62, 3303, 52.2, 2, 60, 2275.8, 3, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12655);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5006, 127, 62, 3314, 247.2, 8, 240, 9103.2, 4, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12654);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5007, 127, 62, 4374, 74.4, 2, 60, 2275.8, 5, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12659);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5008, 127, 61, 4364, 388.2, 13, 390, 14792.7, 0, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12651);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5009, 127, 61, 3413, 60, 2, 60, 2275.8, 1, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12156);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5010, 127, 61, 4363, 157.8, 5, 150, 5689.5, 2, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12650);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5011, 127, 61, 3299, 63, 2, 60, 2275.8, 3, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12154);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5012, 127, 61, 3305, 120.6, 4, 120, 4551.6, 4, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12155);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5013, 127, 61, 3312, 25.8, 1, 30, 1137.9, 5, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12152);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5014, 127, 61, 3297, 178.2, 6, 180, 6827.4, 6, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12151);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5015, 127, 61, 3311, 73.2, 2, 60, 2275.8, 7, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12153);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5016, 127, 61, 4365, 193.2, 6, 180, 6827.4, 8, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12652);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5017, 127, 60, 3409, 60, 2, 60, 2275.8, 0, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12149);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5018, 127, 60, 3410, 108, 4, 120, 4551.6, 1, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12150);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5019, 127, 60, 3295, 27, 1, 30, 1137.9, 2, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12138);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5020, 127, 60, 3310, 85.8, 3, 90, 3413.7, 3, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12139);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5021, 127, 60, 3315, 48, 2, 60, 2275.8, 4, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12147);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5022, 127, 60, 3300, 21, 1, 30, 1137.9, 5, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12146);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5023, 127, 60, 3294, 331.2, 11, 330, 12516.9, 6, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12148);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5024, 127, 60, 3298, 169.8, 6, 180, 6827.4, 7, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12143);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5025, 127, 60, 3317, 40.2, 1, 30, 1137.9, 8, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12141);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5026, 127, 60, 3313, 19.2, 1, 30, 1137.9, 9, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12142);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5027, 127, 60, 3316, 60.6, 2, 60, 2275.8, 10, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12144);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5028, 127, 60, 3296, 36, 1, 30, 1137.9, 11, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12145);
INSERT INTO `tdap_crono_caminhoes` (`id`, `cronograma_id`, `caminhao_id`, `comunidade_id`, `agua_prevista`, `num_viagens`, `agua_entregue`, `vr_total`, `ordem`, `created_at`, `updated_at`, `deleted_at`, `pmda_id`, `pip_pmda_comun_id`) VALUES
	(5029, 127, 60, 3302, 189, 6, 180, 6827.4, 12, '2025-10-30 18:34:17', '2025-10-30 18:37:13', NULL, 1577, 12140);
/*!40000 ALTER TABLE `tdap_crono_caminhoes` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
