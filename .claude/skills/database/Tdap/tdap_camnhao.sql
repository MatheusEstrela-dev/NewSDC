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

-- Copiando estrutura para tabela dbsdc.tdap_caminhao
CREATE TABLE IF NOT EXISTS `tdap_caminhao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `placa` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Placa do Caminhão',
  `cor` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'cor do Caminhão',
  `ano` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ano do Caminhão',
  `marca` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Marca do Caminhão',
  `modelo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Molode do Caminhão',
  `capacidade` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Capacidade do Tanque do Caminhão',
  `prestador_id` int NOT NULL COMMENT 'Identificador do Prestador',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tdap_caminhao_prestador_id_foreign` (`prestador_id`)
) ENGINE=MyISAM AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_caminhao: 132 rows
/*!40000 ALTER TABLE `tdap_caminhao` DISABLE KEYS */;
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(2, 'GVK-1343', 'AZUL', '1978', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1513', '9', 7, '2025-06-25 19:18:51', '2025-07-01 12:54:27');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(3, 'KUK-5F84', 'VERMELHO', '1982', 'MERCEDES-BENZ', 'MERCEDES-BENZ 2013', '10', 7, '2025-07-01 12:58:20', '2025-07-01 12:58:20');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(4, 'GVH-5989', 'VERMELHO', '1975', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '8,2', 7, '2025-07-01 13:02:11', '2025-07-01 13:02:11');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(5, 'GMA-7518', 'AMARELO', '1980', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '7,5', 7, '2025-07-01 13:04:22', '2025-07-01 13:04:22');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(6, 'GNS-5422', 'VERMELHO', '1972', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '8,45', 7, '2025-07-01 13:06:34', '2025-07-01 13:06:34');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(7, 'GMA-8302', 'BEGE', '1972', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1313', '7,5', 3, '2025-07-01 13:16:58', '2025-07-01 13:16:58');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(8, 'KVT-1D26', 'BRANCO', '2007', 'VOLKSWAGEN', 'VOLKSWAGEN 17180', '8,3', 3, '2025-07-01 13:22:33', '2025-07-01 13:22:33');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(9, 'GVK-5574', 'VERDE', '1978', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '7', 3, '2025-07-01 13:23:27', '2025-07-01 13:23:27');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(10, 'KSY-2G81', 'AZUL', '1979', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1313', '7,3', 3, '2025-07-01 13:24:40', '2025-07-01 13:24:40');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(11, 'HKT-2G40', 'BRANCO', '2009', 'IVECO', 'IVECO 170E22', '13,5', 3, '2025-07-01 13:26:40', '2025-07-01 13:26:40');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(12, 'HLF-1I60', 'BRANCO', '2008', 'IVECO', 'IVECO ECTECTOR 230E2', '14,5', 3, '2025-07-01 13:30:27', '2025-07-01 13:45:25');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(13, 'GYS-2A49', 'VERMELHO', '2003', 'VOLKSWAGEN', 'VOLKSWAGEN 17.220', '7', 3, '2025-07-01 13:31:51', '2025-07-01 13:31:51');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(14, 'CPR-6H31', 'BRANCO', '1983', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1513', '10', 8, '2025-07-01 13:46:32', '2025-07-02 12:50:51');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(15, 'CRH-2761', 'AZUL', '1981', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1513', '9,8', 8, '2025-07-01 13:47:23', '2025-07-01 13:47:23');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(16, 'CCV-8H92', 'BRANCO', '1996', 'FORD', 'FORD F14000', '9,5', 8, '2025-07-01 13:49:19', '2025-07-01 13:49:19');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(17, 'SHC-1E35', 'BRANCA', '2023', 'VOLKSWAGEN', 'VW/ 17.190 CRM 4X2', '12', 5, '2025-07-01 13:52:22', '2025-07-01 13:52:22');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(18, 'GMB-0I87', 'AZUL', '1977', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '10,8', 6, '2025-07-01 13:54:11', '2025-07-01 13:54:11');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(19, 'CRY-5376', 'VERMELHO', '1965', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '8,3', 6, '2025-07-01 13:55:19', '2025-07-01 13:55:19');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(20, 'GUX-7C82', 'BRANCO', '1986', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1524', '14', 6, '2025-07-01 13:56:25', '2025-07-08 12:25:25');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(21, 'KCZ-7677', 'BRANCO', '1996', 'VOLKSWAGEN', 'VOLKSWAGEN 12140', '9,2', 6, '2025-07-01 13:57:39', '2025-07-01 13:57:39');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(22, 'LJS-3277', 'BRANCO', '1975', 'MERCEDES-BENZ', 'MERCEDES-BENZ', '9,25', 6, '2025-07-01 13:58:48', '2025-07-01 13:58:48');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(23, 'BIC-7E36', 'AZUL', '1988', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1114', '9.2', 6, '2025-07-01 13:59:54', '2025-07-08 12:43:31');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(24, 'KDX-2H45', 'VERMELHO', '2000', 'MERCEDES-BENZ', 'MERCEDES-BENZ L 1620', '14,5', 8, '2025-07-01 14:01:22', '2025-07-01 14:01:22');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(25, 'JNW-7F47', 'VERMELHO', '1991', 'VOLKSWAGEN', 'VOLKSWAGEN 11140', '7', 14, '2025-07-01 14:07:07', '2025-07-01 14:07:07');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(26, 'JMW-3D38', 'VERMELHO', '1979', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '8', 14, '2025-07-01 14:08:26', '2025-07-02 12:58:48');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(27, 'MPP-6693', 'AZUL', '1972', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1513', '11,5', 9, '2025-07-01 14:09:13', '2025-07-01 14:09:13');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(28, 'GVI-1431', 'AZUL', '1980', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '9,1', 9, '2025-07-01 14:09:56', '2025-07-01 14:09:56');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(29, 'GPK-1A59', 'AMARELO', '1978', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '10', 9, '2025-07-01 14:11:10', '2025-07-01 14:11:10');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(30, 'BTA-2780', 'BRANCO', '1997', 'GMC', 'GMC14.190', '10,1', 7, '2025-07-01 14:16:00', '2025-07-01 14:16:00');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(31, 'BWU-5989', 'VERDE', '1977', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '10', 6, '2025-07-01 14:42:12', '2025-07-01 14:42:12');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(32, 'BUF-0D15', 'BRANCO', '1995', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1418', '13,2', 9, '2025-07-01 14:43:16', '2025-07-01 14:43:16');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(33, 'CKH-1871', 'VERMELHO', '1975', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1513', '10,5', 7, '2025-07-01 14:44:31', '2025-07-01 14:44:31');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(34, 'GUR-2D84', 'AZUL', '1986', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '8', 8, '2025-07-01 18:32:03', '2025-07-01 18:32:03');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(35, 'BWS-3D93', 'AMARELO', '1979', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1313', '10,2', 7, '2025-07-01 18:34:01', '2025-07-01 18:34:01');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(36, 'GYJ-3C21', 'VERMELHO', '2006', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1418', '10,2', 7, '2025-07-01 18:36:21', '2025-07-01 18:36:21');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(37, 'GVP-3103', 'AZUL', '1983', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1516', '10,5', 7, '2025-07-01 18:37:36', '2025-10-10 12:59:20');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(38, 'HOA-0C77', 'BRANCO', '2010', 'IVECO', 'IVECO 130V18', '8.4', 1, '2025-07-01 18:39:10', '2025-08-28 18:26:29');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(39, 'HLF-6I97', 'AZUL', '1973', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '10.2', 1, '2025-07-01 18:40:21', '2025-08-28 18:26:00');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(40, 'JMN-1H37', 'VERMELHO', '1977', 'MERCEDES-BENZ', 'MERCEDES-BENZ', '11,6', 7, '2025-07-01 18:41:20', '2025-07-01 18:41:20');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(41, 'GMA-5C47', 'AMARELO', '1979', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '10,2', 7, '2025-07-01 18:43:24', '2025-07-01 18:43:24');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(42, 'GKO-6I76', 'AZUL', '1979', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1113', '8', 7, '2025-07-01 18:44:29', '2025-07-01 18:44:29');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(43, 'AEG-1639', 'AZUL', '1982', 'VOLKSWAGEN', 'VOLKSWAGEN 11-130', '7', 7, '2025-07-01 18:45:30', '2025-07-01 18:45:30');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(44, 'RVT-7C47', 'BRANCA', '2023', 'VOLKSWAGEN', 'VW/ 17.190 CRM 4X2', '12', 5, '2025-07-01 18:47:52', '2025-07-01 18:47:52');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(45, 'RTT-4J00', 'BRANCA', '2022', 'MERCEDES-BENZ', 'ATEGO 1719', '13,5', 5, '2025-07-01 19:04:33', '2025-07-01 19:04:33');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(46, 'RUD-1F91', 'BRANCA', '2022', 'MERCEDES-BENZ', 'ATEGO 1419', '11', 5, '2025-07-01 19:12:09', '2025-07-01 19:12:09');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(47, 'SII-1E05', 'BRANCA', '2023', 'MERCEDES-BENZ', 'ATEGO 2730', '26,5', 5, '2025-07-01 19:19:46', '2025-07-01 19:19:46');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(48, 'MLK-0C22', 'BRANCA', '2013', 'MERCEDES-BENZ', 'ATEGO 2429', '19', 5, '2025-07-01 20:33:26', '2025-07-01 20:33:26');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(49, 'BWS-5J97', 'AZUL', '1968', 'MERCEDES-BENZ', 'M.BENZ L 1113', '8', 5, '2025-07-01 20:38:12', '2025-07-01 20:38:12');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(50, 'GMB-1C86', 'AZUL', '1975', 'MERCEDES-BENZ', 'M.BENZ L 1113', '10', 5, '2025-07-01 21:01:39', '2025-07-01 21:01:39');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(51, 'RTT-4I97', 'BRANCA', '2022', 'MERCEDES-BENZ', 'ATEGO 1719', '12', 5, '2025-07-01 21:39:06', '2025-07-01 21:39:06');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(99, 'RVT-7D18', 'BRANCO', '2023', 'VOLKSWAGEN', 'VW 17230', '11', 5, '2025-07-31 12:02:28', '2025-07-31 13:04:16');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(98, 'GNS-1145', 'AZUL', '1970', 'MERCEDES-BENZ', 'MERCEDES BENS 1113', '11', 5, '2025-07-31 11:59:23', '2025-07-31 12:57:03');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(54, 'SHN-5F10', 'BRANCA', '2023', 'VOLKSWAGEN', 'VW/ 17.260 CRM 4X2', '13.5', 5, '2025-07-01 21:43:40', '2025-07-01 21:45:23');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(55, 'RME-2I85', 'BRANCA', '2021', 'VOLKSWAGEN', 'VW/ 17.260 CRM 4X2', '15,5', 5, '2025-07-01 21:53:00', '2025-07-01 21:53:00');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(56, 'SIK-5F00', 'BRANCA', '2023', 'VOLKSWAGEN', 'VW/ 26.260 CRM 6X2', '30', 5, '2025-07-01 21:59:10', '2025-07-01 21:59:10');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(57, 'RTU-0E16', 'BRANCA', '2023', 'VOLKSWAGEN', 'VW/ 17.260 CRM 4X2', '12', 5, '2025-07-01 22:02:30', '2025-07-01 22:02:30');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(58, 'BWY-1D35', 'AMARELA', '1980', 'MERCEDES-BENZ', 'M.BENZ L 1513', '9', 5, '2025-07-01 22:15:53', '2025-07-01 22:15:53');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(59, 'LAF-5H14', 'AMARELA', '1978', 'MERCEDES-BENZ', 'M.BENZ L 1513', '10', 5, '2025-07-01 22:23:24', '2025-07-01 22:23:24');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(60, 'SII-1D15', 'BRANCA', '2023', 'MERCEDES-BENZ', 'M. BENZ/ ATEGO 2730', '30', 5, '2025-07-01 22:31:11', '2025-07-01 22:31:11');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(61, 'NMN-5J46', 'BRANCA (SR SII-1D15)', '2011', 'SELECIONE UMA OPÇÃO', 'SEMI-REBOQUE TANQUE', '30', 5, '2025-07-01 22:40:50', '2025-07-01 22:40:50');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(62, 'SIK-5F12', 'BRANCA', '2023', 'MERCEDES-BENZ', 'M.BENZ/ ATEGO 2730CE', '30', 5, '2025-07-01 22:47:07', '2025-07-01 22:47:07');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(63, 'GMB-3941', 'AZUL', '1985', 'MERCEDES-BENZ', 'M.BENZ L 1313', '10', 3, '2025-07-01 23:02:19', '2025-07-01 23:02:19');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(64, 'BFE-8526', 'AMARELA', '1986', 'MERCEDES-BENZ', 'M.BENZ LA 1113', '8,5', 3, '2025-07-01 23:18:58', '2025-07-01 23:18:58');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(65, 'GTM-1339', 'VERMELHA', '1980', 'MERCEDES-BENZ', 'M.BENZ L 1313', '10', 3, '2025-07-01 23:21:25', '2025-07-01 23:21:25');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(66, 'GUF-0A48', 'VERMELHA', '1980', 'MERCEDES-BENZ', 'M.BENZ L 1113', '8', 3, '2025-07-01 23:25:08', '2025-07-01 23:25:08');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(67, 'GMF-3125', 'PRETA', '1984', 'MERCEDES-BENZ', 'M.BENZ L 1313', '10', 3, '2025-07-01 23:27:34', '2025-07-01 23:27:34');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(68, 'JST-3C80', 'BRANCA', '2010', 'VOLKSWAGEN', 'VW/ 13.180 CNM', '8', 3, '2025-07-01 23:31:47', '2025-07-01 23:31:47');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(69, 'JLZ-7C35', 'BRANCA', '1997', 'GMC', 'GMC 15.190', '14,5', 13, '2025-07-01 23:36:01', '2025-07-01 23:36:01');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(70, 'GVI-1375', 'AZUL', '1975', 'MERCEDES-BENZ', 'M.BENZ L 1113', '11', 13, '2025-07-01 23:41:19', '2025-07-01 23:41:19');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(71, 'GRA-2E26', 'BRANCA', '1994', 'MERCEDES-BENZ', 'M.BENZ L 2318', '17,6', 13, '2025-07-01 23:49:04', '2025-07-01 23:49:04');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(72, 'JOS-8G99', 'PRATA', '2004', 'VOLKSWAGEN', 'VW/ 23.220', '16,5', 13, '2025-07-01 23:53:07', '2025-07-01 23:53:07');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(73, 'GVI-1394', 'AZUL', '1977', 'MERCEDES-BENZ', 'M.BENZ L 1113', '10,5', 13, '2025-07-02 00:00:49', '2025-07-02 00:00:49');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(74, 'GTK-8D44', 'BRANCA', '1991', 'MERCEDES-BENZ', 'M.BENZ L 1618', '15', 7, '2025-07-02 00:04:37', '2025-07-02 00:04:37');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(75, 'GMB-3148', 'AZUL', '1975', 'MERCEDES-BENZ', 'M.BENZ L 1513', '10', 7, '2025-07-02 00:06:36', '2025-07-02 00:06:36');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(76, 'GYS-2009', 'BRANCA', '2000', 'MERCEDES-BENZ', 'M.BENZ L 1418', '10', 7, '2025-07-02 00:09:06', '2025-07-02 00:09:06');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(77, 'GKT-9064', 'AZUL', '1971', 'MERCEDES-BENZ', 'M.BENZ L 1113', '8', 7, '2025-07-02 00:19:09', '2025-07-02 00:19:09');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(78, 'GMG-3A29', 'VERMELHA', '1982', 'MERCEDES-BENZ', 'M.BENZ LK 1113', '9,5', 7, '2025-07-02 00:23:54', '2025-07-02 00:23:54');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(79, 'XXX-0000', '', '', 'MERCEDES-BENZ', '', '000', 7, '2025-07-02 00:27:25', '2025-07-17 21:33:50');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(80, 'BWM-8028', 'AZUL', '1978', 'MERCEDES-BENZ', 'M.BENZ L 1113', '10', 7, '2025-07-02 00:29:25', '2025-07-02 00:29:25');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(81, 'GTM-8I43', 'BRANCA', '2006', 'MERCEDES-BENZ', 'M. BENZ/ ATEGO 1418', '10', 7, '2025-07-02 00:40:18', '2025-07-02 00:40:18');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(82, 'JLR-6830', 'GRENA', '1988', 'MERCEDES-BENZ', 'M.BENZ L 1518', '16', 7, '2025-07-02 00:47:59', '2025-07-02 00:47:59');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(83, 'HMV-4530', 'PRATA', '2010', 'FORD', 'FORD/ CARGO 2428 E', '16', 7, '2025-07-02 00:53:10', '2025-07-02 00:53:10');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(84, 'DVT-1469', 'VERMELHA', '2007', 'MERCEDES-BENZ', 'M. BENZ/ ATEGO 1315', '10', 7, '2025-07-02 00:57:43', '2025-07-02 00:57:43');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(85, 'POJ-3D59', 'BRANCA', '2018', 'MERCEDES-BENZ', 'M. BENZ/ ATEGO 1719', '10', 10, '2025-07-02 01:06:58', '2025-07-02 01:06:58');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(86, 'GKO-0J53', 'VERMELHA', '1986', 'MERCEDES-BENZ', 'M.BENZ L 1113', '14,5', 14, '2025-07-02 01:11:14', '2025-07-02 01:11:14');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(87, 'GVP-3480', 'VERMELHA', '1987', 'VOLKSWAGEN', 'VW/ 14.140', '8', 14, '2025-07-02 01:14:12', '2025-07-02 01:14:12');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(88, 'JLZ-6699', 'AZUL', '1979', 'MERCEDES-BENZ', 'M.BENZ L 1313', '11', 11, '2025-07-02 12:44:54', '2025-07-02 12:44:54');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(89, 'GVI-1E64', 'VERMELHA', '1998', 'MERCEDES-BENZ', 'M.BENZ 1214 C', '8,7', 11, '2025-07-02 13:19:59', '2025-07-02 13:19:59');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(90, 'GQB-3870', 'VERMELHA', '1983', 'MERCEDES-BENZ', 'M.BENZ L 1113', '8,5', 11, '2025-07-02 13:33:34', '2025-07-02 13:33:34');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(91, 'KCT-9H04', 'AZUL', '1979', 'MERCEDES-BENZ', 'M.BENZ L 1113', '11', 11, '2025-07-02 13:42:43', '2025-07-02 13:42:43');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(92, 'HMM-7982', 'AZUL', '1969', 'MERCEDES-BENZ', 'M.BENZ L 1111', '11', 11, '2025-07-02 13:53:23', '2025-07-02 13:53:23');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(93, 'GQT-3010', 'ROXA', '1994', 'MERCEDES-BENZ', 'M.BENZ L 1214', '10,5', 11, '2025-07-02 13:56:01', '2025-07-02 13:56:01');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(94, 'GVK-0457', 'AZUL', '1978', 'MERCEDES-BENZ', 'M.BENZ L 1113', '8', 11, '2025-07-02 14:08:30', '2025-07-02 14:08:30');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(95, 'GMM-8G68', 'AMARELA', '1978', 'MERCEDES-BENZ', 'M.BENZ L 1113', '9,5', 11, '2025-07-02 14:16:20', '2025-07-02 14:16:20');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(97, 'AAA-4545', '4', '1922', 'MERCEDES-BENZ', '232', '2', 1, '2025-07-16 13:09:28', '2025-07-16 13:09:28');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(100, 'RMI-2C09', 'BRANCO', '2021', 'VOLKSWAGEN', 'VW17260', '15,5', 5, '2025-07-31 12:14:38', '2025-07-31 12:14:38');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(101, 'RVT-7B87', 'BRANCO', '2023', 'VOLKSWAGEN', 'VW17190', '12', 5, '2025-07-31 12:15:42', '2025-07-31 12:15:42');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(102, 'RUD-1F97', 'BRANCO', '2922', 'MERCEDES-BENZ', 'MB ATEGO 1413', '12', 5, '2025-07-31 12:17:31', '2025-07-31 12:17:31');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(103, 'JRX-8G28', 'AZUL', '1974', 'MERCEDES-BENZ', 'MB 1113', '10', 5, '2025-07-31 12:28:47', '2025-09-23 19:40:06');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(104, 'MUD-3A50', 'AMARELO', '1975', 'MERCEDES-BENZ', 'MB 1113', '9', 5, '2025-07-31 12:32:31', '2025-07-31 12:32:31');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(105, 'RTI-7E22', 'BRANCO', '2022', 'VOLKSWAGEN', 'VW17260', '12', 5, '2025-07-31 12:33:31', '2025-07-31 12:33:31');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(106, 'RMI-2C11', 'BRANCO', '2021', 'VOLKSWAGEN', 'VW 17260', '15,5', 5, '2025-07-31 12:34:46', '2025-07-31 12:34:46');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(107, 'MPR-6623', 'VERMELHO', '1986', 'MERCEDES-BENZ', 'MB 1313', '11', 7, '2025-07-31 12:36:07', '2025-07-31 12:36:07');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(108, 'GYJ-3C21', 'VERMELHO', '2006', 'MERCEDES-BENZ', 'MERCEDES-BENZ 1418', '10,2', 1, '2025-08-11 13:39:12', '2025-08-11 13:39:12');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(109, 'JMP-7E96', 'AZUL', '1986', 'MERCEDES-BENZ', 'MERCEDES-BENZ L 1513', '14.5', 7, '2025-08-29 19:14:34', '2025-08-29 19:15:53');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(110, 'GNT-7A17', 'AZUL', '1984', 'MERCEDES-BENZ', 'MERCEDES-BENZ L 1513', '10.5', 7, '2025-08-29 19:31:59', '2025-08-29 19:31:59');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(111, 'RME-2I81', 'BRANCA', '2021', 'VOLKSWAGEN', 'VW/ 17.260 CRM 4X2', '15', 5, '2025-09-02 21:03:37', '2025-09-02 21:03:37');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(112, 'RME-2I83', 'BRANCA', '2021', 'VOLKSWAGEN', 'VW/ 17.260 CRM 4X2', '15', 5, '2025-09-02 21:10:23', '2025-09-02 21:11:30');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(113, 'HZK-3J36', 'AZUL', '1978', 'MERCEDES-BENZ', 'M.BENZ L 1113', '8', 5, '2025-09-04 22:47:23', '2025-09-04 22:47:23');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(114, 'HZJ-8B15', 'BRANCA', '1990', 'MERCEDES-BENZ', 'M.BENZ L 1114', '16', 3, '2025-09-04 23:02:22', '2025-10-09 10:59:59');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(115, 'HMM-1B31', 'AZUL', '1986', 'MERCEDES-BENZ', 'M.BENZ L 1113', '10.6', 7, '2025-09-04 23:12:34', '2025-09-04 23:12:34');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(116, 'GLL-3C54', 'BEGE', '1984', 'MERCEDES-BENZ', 'M.BENZ L 1513', '9', 7, '2025-09-04 23:20:18', '2025-09-04 23:20:18');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(117, 'GKO-6247', 'AZUL', '1981', 'MERCEDES-BENZ', 'M.BENZ L 1313', '10', 7, '2025-09-04 23:25:50', '2025-09-10 13:44:29');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(118, 'BWS-3D93', 'AMARELO', '1979', 'MERCEDES-BENZ', 'MERCEDES BENS 1313', '10.2', 2, '2025-09-09 17:42:14', '2025-09-09 17:42:14');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(119, 'JMN-1H37', 'VERMELHO', '1977', 'MERCEDES-BENZ', 'MERCEDES BENS', '11,6', 2, '2025-09-09 17:44:34', '2025-09-09 17:44:34');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(120, 'GVP-3103', 'AZUL', '1983', 'MERCEDES-BENZ', 'MERCEDES BENS 1516', '10,5', 2, '2025-09-09 17:45:55', '2025-10-10 13:01:07');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(121, 'HLF-1I60', 'BRANCO', '2008', 'IVECO', 'IVECO ECTECTOR 23OE2', '14,5', 7, '2025-09-10 18:19:27', '2025-09-10 18:19:27');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(122, 'AEG-1639', 'AZUL', '1982', 'VOLKSWAGEN', 'VOLKSWAGEN 11-130', '7', 14, '2025-09-12 13:38:03', '2025-09-12 13:38:03');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(123, 'GKO-6I76', 'AZUL', '1979', 'MERCEDES-BENZ', 'MERCEDES BENS 1113', '8', 14, '2025-09-12 13:39:05', '2025-09-15 12:08:01');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(124, 'DVT-1469', 'VERMELHO', '2007', 'MERCEDES-BENZ', 'M.BENZ/ ATEGO 1315', '10', 3, '2025-09-12 18:16:30', '2025-09-12 18:16:30');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(125, 'GMA-7518', 'AMARELO', '1980', 'MERCEDES-BENZ', 'MERCEDES BENS 1113', '7,5', 3, '2025-09-15 13:53:15', '2025-09-15 13:53:15');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(126, 'MPR-6623', 'VERMELHO', '1986', 'MERCEDES-BENZ', 'MB 1313', '11', 3, '2025-09-15 13:55:24', '2025-09-15 13:55:24');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(127, 'BWM-8028', 'AZUL', '1978', 'MERCEDES-BENZ', 'M.BENZ L 1113', '10', 3, '2025-09-16 17:42:16', '2025-09-16 17:42:16');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(128, 'CKH-1871', 'VERMELHO', '1975', 'MERCEDES-BENZ', 'MERCEDES-BENS 1513', '10,5', 3, '2025-09-16 18:41:09', '2025-09-16 18:41:09');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(129, 'GVH-5989', 'VERMELHO', '1975', 'MERCEDES-BENZ', 'MERCEDES BENS 1113', '8,2', 3, '2025-09-22 14:03:04', '2025-09-22 14:03:04');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(130, 'HMM-1B31', 'AZUL', '1986', 'MERCEDES-BENZ', 'M.BENZ L 1113', '10,6', 3, '2025-09-22 15:32:47', '2025-09-22 15:32:47');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(131, 'JUD-3A30', 'VERMELHO', '1968', 'MERCEDES-BENZ', 'MERCEDES BENS 1111', '10', 5, '2025-09-23 14:50:31', '2025-09-23 14:50:31');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(132, 'IKK-1G22', 'AMARELO', '1984', 'MERCEDES-BENZ', 'MB LS 1113', '9', 7, '2025-09-25 19:47:24', '2025-10-03 11:32:14');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(133, 'AAA-0000', 'AZUL', '1999', 'MERCEDES-BENZ', '1113', '9', 16, '2025-09-29 19:32:33', '2025-09-29 19:32:33');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(134, 'BWA-6I04', 'BRANCO', '1992', 'MERCEDES-BENZ', 'M.BENZ 1214', '11,5', 11, '2025-10-08 12:15:07', '2025-10-08 12:15:07');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(135, 'GMG-3480', 'VERMELHO', '1976', 'MERCEDES-BENZ', 'MERCEDES BENS 1113', '9,5', 11, '2025-10-08 12:16:21', '2025-10-08 12:16:21');
INSERT INTO `tdap_caminhao` (`id`, `placa`, `cor`, `ano`, `marca`, `modelo`, `capacidade`, `prestador_id`, `created_at`, `updated_at`) VALUES
	(136, 'GMG-3480', 'VERMELHO', '1976', 'MERCEDES-BENZ', 'MERCEDES BENS 1113', '9,5', 11, '2025-10-08 12:16:24', '2025-10-08 12:16:24');
/*!40000 ALTER TABLE `tdap_caminhao` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
