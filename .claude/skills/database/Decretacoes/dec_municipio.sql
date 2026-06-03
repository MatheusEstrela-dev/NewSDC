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

-- Copiando estrutura para tabela dbsdc.dec_decreto_municipios
CREATE TABLE IF NOT EXISTS `dec_decreto_municipios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entrada_processos_id` int unsigned NOT NULL,
  `n_protocolo_fide` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipio_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6523 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.dec_decreto_municipios: ~210 rows (aproximadamente)
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(7, 2, 'MG-F-3170404-14110-20231222', 7040, '2025-07-18 14:48:14', '2025-07-18 14:48:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(8, 3, 'MG-F-3141504-13214-20231219', 4150, '2025-07-18 15:55:44', '2025-07-18 15:55:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(9, 4, 'MG-F-3147006-13214-20231223', 4700, '2025-07-18 17:02:33', '2025-07-18 17:02:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(10, 5, 'MG-F-3126802-14120-20231228', 2680, '2025-07-18 17:38:56', '2025-07-18 17:38:56');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(17, 6, 'MG-F-3123700-13213-20231219', 2370, '2025-08-05 15:19:17', '2025-08-05 15:19:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(25, 7, 'MG-F-3154408-13214-20250325', 5440, '2025-08-07 11:48:00', '2025-08-07 11:48:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(29, 10, '', 90, '2025-09-02 14:38:31', '2025-09-02 14:38:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(30, 11, '', 90, '2025-09-02 14:38:38', '2025-09-02 14:38:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(34, 12, 'MG-F-3140852-14120-20250818', 4080, '2025-09-09 12:20:14', '2025-09-09 12:20:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(35, 8, 'MG-F-3156452-11321-20250425', 5645, '2025-09-09 12:31:19', '2025-09-09 12:31:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(36, 13, NULL, 30, '2025-09-09 12:45:44', '2025-09-09 12:45:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(37, 13, NULL, 180, '2025-09-09 12:45:44', '2025-09-09 12:45:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(38, 13, NULL, 170, '2025-09-09 12:45:44', '2025-09-09 12:45:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(39, 13, NULL, 163, '2025-09-09 12:45:44', '2025-09-09 12:45:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(40, 13, NULL, 140, '2025-09-09 12:45:44', '2025-09-09 12:45:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(41, 13, NULL, 150, '2025-09-09 12:45:44', '2025-09-09 12:45:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(42, 9, '', 90, '2025-09-09 13:18:18', '2025-09-09 13:18:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(43, 14, NULL, 130, '2025-09-12 12:36:18', '2025-09-12 12:36:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(44, 15, NULL, 6180, '2025-09-22 12:25:48', '2025-09-22 12:25:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(52, 20, NULL, 3065, '2025-10-09 14:35:40', '2025-10-09 14:35:40');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(53, 21, NULL, 3700, '2025-10-10 13:09:21', '2025-10-10 13:09:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(55, 22, NULL, 3700, '2025-10-10 13:21:02', '2025-10-10 13:21:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(58, 24, NULL, 1700, '2025-10-16 16:22:31', '2025-10-16 16:22:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(60, 18, NULL, 6935, '2025-10-16 17:37:53', '2025-10-16 17:37:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(66, 19, 'MG-F-3108206-14120-20251003', 820, '2025-10-17 14:56:04', '2025-12-30 17:40:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(67, 17, 'MG-F-3170305-14120-20250930', 7030, '2025-10-17 15:03:10', '2025-12-30 17:26:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(69, 16, 'MG-F-3146255-14120-20250923', 4625, '2025-10-17 15:21:18', '2026-01-05 13:38:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(3943, 28, 'MG-F-3109303-14131-20250901', 930, '2025-11-03 12:33:34', '2025-11-03 12:33:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(3944, 29, 'MG-F-3154457-14110-20251009', 5445, '2025-11-03 12:50:23', '2025-11-03 12:50:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(3948, 26, 'MG-F-3146206-13214-20251019', 4620, '2025-11-03 15:07:44', '2026-01-05 13:34:28');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(3951, 27, 'MG-F-3121209-13215-20251030', 2120, '2025-11-03 15:10:11', '2026-01-05 16:54:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(4020, 30, 'MG-F-3156908-13214-20251102', 5690, '2025-11-03 17:13:55', '2025-11-03 17:13:55');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5903, 23, 'MG-F-3101003-14120-20251016', 100, '2025-11-05 18:35:44', '2026-01-26 15:36:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5904, 23, 'MG-F-3101706-14120-20251018', 170, '2025-11-05 18:35:44', '2026-01-26 15:37:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5905, 23, 'MG-F-3104452-14120-20251029', 445, '2025-11-05 18:35:44', '2026-01-26 15:38:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5906, 23, 'MG-F-3104502-14120-20251022', 450, '2025-11-05 18:35:44', '2026-01-26 15:38:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5907, 23, 'MG-F-3104809-14120-20251022', 480, '2025-11-05 18:35:44', '2026-01-26 15:38:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5908, 23, 'MG-F-3106507-14120-20251022', 650, '2025-11-05 18:35:44', '2026-01-26 15:39:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5909, 23, 'MG-F-3108503-14120-20251015', 850, '2025-11-05 18:35:44', '2026-01-26 15:39:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5910, 23, 'MG-F-3108602-14120-20251020', 860, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5911, 23, 'MG-F-3109204-14120-20251020', 920, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5912, 23, 'MG-F-3102704-14120-20251021', 270, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5913, 23, 'MG-F-3111150-14120-20251016', 1115, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5915, 23, 'MG-F-3112653-14120-20251015', 1265, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5916, 23, 'MG-F-3113503-14120-20251030', 1350, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5917, 23, 'MG-F-3116100-14120-20251015', 1610, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5919, 23, 'MG-F-3116506-14120-20251017', 1650, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5921, 23, 'MG-F-3116803-14120-20251020', 1680, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5922, 23, 'MG-F-3117009-14120-20251016', 1700, '2025-11-05 18:35:44', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5923, 23, 'MG-F-3117836-14120-20251104', 1783, '2025-11-05 18:35:44', '2026-01-26 15:43:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5924, 23, 'MG-F-3118809-14120-20251015', 1880, '2025-11-05 18:35:44', '2026-01-26 15:43:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5925, 23, 'MG-F-3119500-14120-20251031', 1950, '2025-11-05 18:35:44', '2026-01-26 15:43:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5926, 23, 'MG-F-3120870-14120-20251015', 2087, '2025-11-05 18:35:44', '2026-01-26 15:43:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5927, 23, 'MG-F-3122355-14120-20251030', 2235, '2025-11-05 18:35:44', '2026-01-26 15:44:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5928, 23, 'MG-F-3123809-14120-20251017', 2380, '2025-11-05 18:35:44', '2026-01-26 15:44:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5929, 23, 'MG-F-3124302-14120-20251030', 2430, '2025-11-05 18:35:44', '2026-01-26 15:44:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5931, 23, 'MG-F-3125606-14120-20251015', 2560, '2025-11-05 18:35:44', '2026-01-26 15:45:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5932, 23, 'MG-F-3126505-14120-20251030', 2650, '2025-11-05 18:35:44', '2026-01-26 15:45:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5933, 23, 'MG-F-3127305-14120-20251016', 2730, '2025-11-05 18:35:44', '2026-01-26 15:47:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5934, 23, 'MG-F-3127339-14120-20251016', 2733, '2025-11-05 18:35:44', '2026-01-26 15:47:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5935, 23, 'MG-F-3127354-14120-20251020', 2735, '2025-11-05 18:35:44', '2026-01-26 15:47:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5936, 23, 'MG-F-3127800-14120-20251017', 2780, '2025-11-05 18:35:44', '2026-01-26 15:47:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5937, 23, 'MG-F-3129608-14120-20251017', 2960, '2025-11-05 18:35:44', '2026-01-26 15:48:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5938, 23, 'MG-F-3129657-14120-20251017', 2965, '2025-11-05 18:35:44', '2026-01-26 15:48:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5939, 23, 'MG-F-3130051-14120-20251022', 3005, '2025-11-05 18:35:44', '2026-01-26 15:48:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5940, 23, 'MG-F-3132503-14120-20251017', 3250, '2025-11-05 18:35:44', '2026-01-26 15:48:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5941, 23, 'MG-F-3133303-14120-20251017', 3330, '2025-11-05 18:35:44', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5942, 23, 'MG-F-3134004-14120-20251028', 3400, '2025-11-05 18:35:44', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5943, 23, 'MG-F-3135050-14120-20251015', 3505, '2025-11-05 18:35:44', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5944, 23, 'MG-F-3135100-14120-20251020', 3510, '2025-11-05 18:35:44', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5945, 23, 'MG-F-3135209-14120-20251015', 3520, '2025-11-05 18:35:44', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5946, 23, 'MG-F-3136405-14120-20251031', 3640, '2025-11-05 18:35:44', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5947, 23, 'MG-F-3136959-14120-20251014', 3695, '2025-11-05 18:35:44', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5948, 23, 'MG-F-3137007-14120-20250923', 3700, '2025-11-05 18:35:44', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5949, 23, 'MG-F-3137304-14120-20251021', 3730, '2025-11-05 18:35:44', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5951, 23, 'MG-F-3138658-14120-20251017', 3865, '2025-11-05 18:35:44', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5952, 23, 'MG-F-3138906-14120-20251023', 3890, '2025-11-05 18:35:44', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5953, 23, 'MG-F-3141009-14120-20251021', 4100, '2025-11-05 18:35:44', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5954, 23, 'MG-F-3141405-14120-20251020', 4140, '2025-11-05 18:35:44', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5955, 23, 'MG-F-3142007-14120-20251016', 4200, '2025-11-05 18:35:44', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5956, 23, 'MG-F-3142254-14120-20251015', 4225, '2025-11-05 18:35:44', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5957, 23, 'MG-F-3142809-14120-20251017', 4280, '2025-11-05 18:35:44', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5958, 23, 'MG-F-3142908-14120-20251018', 4290, '2025-11-05 18:35:44', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5959, 23, 'MG-F-3145059-14120-20251020', 4505, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5960, 23, 'MG-F-3145307-14120-20251022', 4530, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5961, 23, 'MG-F-3147956-14120-20251022', 4795, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5962, 23, 'MG-F-3148707-14120-20251021', 4870, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5963, 23, 'MG-F-3150000-14120-20251015', 5000, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5964, 23, 'MG-F-3150570-14120-20251015', 5057, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5965, 23, 'MG-F-3151206-14120-20251020', 5120, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5966, 23, 'MG-F-3152131-14120-20251016', 5213, '2025-11-05 18:35:44', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5967, 23, 'MG-F-3152170-14120-20251104', 5217, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5968, 23, 'MG-F-3152402-14120-20251015', 5240, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5971, 23, 'MG-F-3154507-14120-20251015', 5450, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5973, 23, 'MG-F-3156502-14120-20251016', 5650, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5974, 23, 'MG-F-3156601-14120-20251015', 5660, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5975, 23, 'MG-F-3157005-14120-20251017', 5700, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5976, 23, 'MG-F-3157609-14120-20251015', 5760, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5977, 23, 'MG-F-3161106-14120-20251020', 6110, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5978, 23, 'MG-F-3162450-14120-20251015', 6245, '2025-11-05 18:35:44', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5979, 23, 'MG-F-3168606-14120-20251015', 6860, '2025-11-05 18:35:44', '2025-11-27 18:11:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5980, 23, 'MG-F-3169356-14120-20251015', 6935, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5981, 23, 'MG-F-3169703-14120-20251015', 6970, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5983, 23, 'MG-F-3170529-14120-20251022', 7052, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5984, 23, 'MG-F-3170651-14120-20251020', 7065, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5985, 23, 'MG-F-3170800-14120-20251030', 7080, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5986, 23, 'MG-F-3170909-14120-20251002', 7090, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5987, 23, 'MG-F-3171030-14120-20251015', 7103, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5988, 23, 'MG-F-3171600-14120-20251015', 7160, '2025-11-05 18:35:44', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5989, 31, 'MG-F-3138807-13213-20251031', 3880, '2025-11-06 13:46:04', '2025-11-06 13:46:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5990, 25, 'MG-F-3148509-13215-20251019', 4850, '2025-11-06 13:54:10', '2025-11-06 13:54:10');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5991, 32, 'MG-F-3138401-13215-20251104', 3840, '2025-11-10 15:43:24', '2026-01-05 14:09:30');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5992, 33, 'MG-F-3145307-13214-20251106', 4530, '2025-11-10 15:55:20', '2026-01-05 16:05:40');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5993, 34, 'MG-F-3147006-14132-20251104', 4700, '2025-11-10 17:38:42', '2025-11-10 17:43:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5994, 35, 'MG-F-3125200-13215-20251107', 2520, '2025-11-10 17:54:23', '2026-01-05 14:47:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5995, 36, NULL, 2520, '2025-11-10 17:56:49', '2025-11-10 17:56:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5996, 37, 'MG-F-3130507-13214-20251102', 3050, '2025-11-12 13:15:40', '2026-01-05 14:42:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5997, 38, 'MG-F-3103405-12300-20251109', 340, '2025-11-12 18:57:19', '2025-11-12 19:02:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5998, 39, 'MG-F-3107406-13213-20251108', 740, '2025-11-12 19:06:21', '2025-11-12 19:11:16');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(5999, 40, 'MG-F-3115201-13215-20251108', 1520, '2025-11-12 19:13:51', '2025-11-12 19:17:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6000, 41, 'MG-F-3130309-13214-20251108', 3030, '2025-11-12 19:20:14', '2025-11-17 11:38:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6001, 42, 'MG-F-3130309-13215-20251108', 3030, '2025-11-12 19:24:58', '2025-11-12 19:28:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6002, 43, 'MG-F-3128105-13215-20251108', 2810, '2025-11-13 12:45:34', '2025-11-13 12:52:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6003, 44, 'MG-F-3112703-13215-20251106', 1270, '2025-11-13 12:57:24', '2025-11-13 13:01:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6005, 46, 'MG-F-3143302-13213-20251105', 4330, '2025-11-13 13:11:57', '2025-11-13 13:14:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6006, 47, 'MG-F-3103504-13214-20251105', 350, '2025-11-13 13:16:17', '2026-01-05 14:38:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6007, 48, 'MG-F-3127107-13215-20251105', 2710, '2025-11-13 13:22:04', '2025-11-13 13:25:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6008, 49, 'MG-F-3161304-13215-20251105', 6130, '2025-11-13 13:26:31', '2025-11-13 13:27:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6009, 50, 'MG-F-3145901-13215-20251104', 4590, '2025-11-13 13:29:30', '2025-11-13 13:31:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6010, 51, 'MG-F-3138005-13215-20251104', 3800, '2025-11-13 13:32:33', '2025-11-13 13:38:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6011, 52, 'MG-F-3115102-13215-20251102', 1510, '2025-11-13 13:49:02', '2025-11-13 13:51:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6012, 53, 'MG-F-3115607-13214-20251101', 1560, '2025-11-13 13:56:22', '2025-11-13 13:59:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6013, 54, 'MG-F-3107406-13214-20251101', 740, '2025-11-13 14:02:24', '2025-11-13 14:05:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6015, 56, 'MG-F-3165701-12300-20251030', 6570, '2025-11-13 14:21:46', '2025-11-13 14:24:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6016, 57, 'MG-F-3170404-13215-20251030', 7040, '2025-11-13 14:28:36', '2025-11-13 14:31:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6017, 58, 'MG-F-3147105-14132-20251029', 4710, '2025-11-13 14:33:54', '2025-11-13 14:35:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6018, 59, 'MG-F-3129657-14132-20251023', 2965, '2025-11-13 16:05:20', '2025-11-13 16:08:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6019, 60, 'MG-F-3138906-14132-20251019', 3890, '2025-11-13 16:12:18', '2025-11-13 16:14:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6020, 61, 'MG-F-3110509-13215-20251019', 1050, '2025-11-13 16:17:34', '2025-11-13 16:19:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6021, 62, 'MG-F-3111804-13214-20251018', 1180, '2025-11-13 16:21:46', '2025-11-13 16:23:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6022, 63, 'MG-F-3169307-23110-20251018', 6930, '2025-11-13 16:25:46', '2025-11-13 16:27:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6023, 64, 'MG-F-3109204-23120-20251017', 920, '2025-11-13 16:29:16', '2025-11-13 16:31:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6024, 65, 'MG-F-3139409-13214-20251014', 3940, '2025-11-13 16:33:14', '2025-11-13 16:35:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6025, 66, 'MG-F-3170404-24100-20251014', 7040, '2025-11-13 16:39:14', '2025-11-13 16:40:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6026, 67, 'MG-F-3170909-14131-20251011', 7090, '2025-11-13 16:43:09', '2025-11-13 16:46:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6027, 68, 'MG-F-3167806-23120-20251008', 6780, '2025-11-13 16:50:47', '2025-11-13 16:55:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6028, 69, 'MG-F-3104205-14131-20251008', 420, '2025-11-13 16:58:57', '2025-11-13 17:03:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6029, 70, 'MG-F-3169307-14131-20251007', 6930, '2025-11-13 17:05:33', '2025-11-13 17:08:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6030, 71, 'MG-F-3112653-14110-20251007', 1265, '2025-11-13 17:10:29', '2025-11-13 17:12:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6031, 72, 'MG-F-3140852-14131-20251006', 4085, '2025-11-13 17:14:45', '2025-11-13 17:16:05');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6032, 73, 'MG-F-3134400-22220-20251005', 3440, '2025-11-13 17:18:29', '2025-11-13 17:20:29');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6033, 74, 'MG-F-3167202-14131-20251005', 6720, '2025-11-13 17:23:07', '2025-11-13 17:24:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6034, 75, 'MG-F-3167202-14131-20251003', 6720, '2025-11-13 17:26:50', '2025-11-13 17:28:03');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6035, 76, 'MG-F-3167202-14131-20251002', 6720, '2025-11-13 17:29:40', '2025-11-13 17:31:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6036, 77, 'MG-F-3116159-14131-20251001', 1615, '2025-11-13 17:32:32', '2025-11-13 17:33:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6037, 78, 'MG-F-3110400-14132-20251001', 1040, '2025-11-13 17:35:56', '2025-11-13 17:37:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6038, 79, 'MG-F-3167202-14131-20251001', 6720, '2025-11-13 17:39:01', '2025-11-13 17:40:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6039, 80, 'MG-F-3133808-12300-20251102', 3380, '2025-11-13 18:04:35', '2025-11-13 18:05:30');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6040, 23, 'MG-F-3106655-14120-20251020', 665, '2025-11-17 14:32:03', '2026-01-26 15:39:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6041, 23, 'MG-F-3108255-14120-20251015', 825, '2025-11-17 14:32:03', '2026-01-26 15:39:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6042, 23, 'MG-F-3109402-14120-20251107', 940, '2025-11-17 14:32:03', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6043, 23, 'MG-F-3112703-14120-20251020', 1270, '2025-11-17 14:32:03', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6044, 23, 'MG-F-3113701-14120-20251020', 1370, '2025-11-17 14:32:03', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6045, 23, 'MG-F-3115474-14120-20251021', 1547, '2025-11-17 14:32:03', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6046, 23, 'MG-F-3116159-14120-20251021', 1615, '2025-11-17 14:32:03', '2026-01-26 15:42:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6047, 23, 'MG-F-3120300-14120-20251022', 2030, '2025-11-17 14:32:03', '2026-01-26 15:43:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6048, 23, 'MG-F-3122454-14120-20251022', 2245, '2025-11-17 14:32:03', '2026-01-26 15:44:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6049, 23, 'MG-F-3125408-14120-20251015', 2540, '2025-11-17 14:32:03', '2026-01-26 15:44:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6050, 23, 'MG-F-3126604-14120-20251020', 2660, '2025-11-17 14:32:03', '2026-01-26 15:45:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6051, 23, 'MG-F-3126703-14120-20251021', 2670, '2025-11-17 14:32:03', '2026-01-26 15:45:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6052, 23, 'MG-F-3127073-14120-20251018', 2707, '2025-11-17 14:32:03', '2026-01-26 15:45:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6053, 23, 'MG-F-3128253-14120-20251021', 2825, '2025-11-17 14:32:03', '2026-01-26 15:47:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6054, 23, 'MG-F-3130655-14120-20251101', 3065, '2025-11-17 14:32:03', '2026-01-26 15:48:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6055, 23, 'MG-F-3135357-14120-20251015', 3535, '2025-11-17 14:32:03', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6056, 23, 'MG-F-3135605-14120-20251020', 3560, '2025-11-17 14:32:03', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6057, 23, 'MG-F-3135803-14120-20251105', 3580, '2025-11-17 14:32:03', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6058, 23, 'MG-F-3136009-14120-20251022', 3600, '2025-11-17 14:32:03', '2026-01-26 15:51:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6059, 23, 'MG-F-3136520-14120-20251020', 3652, '2025-11-17 14:32:03', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6060, 23, 'MG-F-3136579-14120-20251016', 3657, '2025-11-17 14:32:03', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6061, 23, 'MG-F-3136801-14120-20251022', 3680, '2025-11-17 14:32:03', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6062, 23, 'MG-F-3138351-14120-20251015', 3835, '2025-11-17 14:32:03', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6063, 23, 'MG-F-3138682-14120-20251021', 3868, '2025-11-17 14:32:03', '2026-01-26 15:53:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6064, 23, 'MG-F-3139250-14120-20251015', 3925, '2025-11-17 14:32:03', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6065, 23, 'MG-F-3139300-14120-20251022', 3930, '2025-11-17 14:32:03', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6066, 23, 'MG-F-3141801-14120-20251106', 4180, '2025-11-17 14:32:03', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6067, 23, 'MG-F-3142700-14120-20251021', 4270, '2025-11-17 14:32:03', '2026-01-26 15:55:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6068, 23, 'MG-F-3143153-14120-20251105', 4315, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6069, 23, 'MG-F-3143302-14120-20251022', 4330, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6070, 23, 'MG-F-3143450-14120-20251101', 4345, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6071, 23, 'MG-F-3145372-14120-20251101', 4537, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6072, 23, 'MG-F-3145455-14120-20251105', 4545, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6073, 23, 'MG-F-3146552-14120-20251022', 4655, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6074, 23, 'MG-F-3149150-14120-20251021', 4915, '2025-11-17 14:32:03', '2026-01-26 15:58:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6075, 23, 'MG-F-3152204-14120-20251020', 5220, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6076, 23, 'MG-F-3155603-14120-20251020', 5560, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6077, 23, 'MG-F-3157377-14120-20251020', 5737, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6078, 23, 'MG-F-3158102-14120-20251001', 5810, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6079, 23, 'MG-F-3160454-14120-20251022', 6045, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6080, 23, 'MG-F-3162252-14120-20251021', 6225, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6081, 23, 'MG-F-3162658-14120-20251105', 6265, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6082, 23, 'MG-F-3162708-14120-20251101', 6270, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6083, 23, 'MG-F-3165909-14120-20251105', 6590, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6084, 23, 'MG-F-3166956-14120-20251016', 6695, '2025-11-17 14:32:03', '2026-01-26 16:02:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6085, 23, 'MG-F-3168002-14120-20251022', 6800, '2025-11-17 14:32:03', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6086, 23, 'MG-F-3170008-14120-20251016', 7000, '2025-11-17 14:32:03', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6087, 23, 'MG-F-3171071-14120-20251020', 7107, '2025-11-17 14:32:03', '2026-01-26 16:05:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6088, 81, 'MG-F-3106101-13215-20251105', 610, '2025-11-17 17:34:21', '2025-11-17 17:34:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6089, 82, 'MG-F-3134301-13215-20251108', 3430, '2025-11-17 17:41:47', '2025-11-17 17:41:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6090, 83, 'MG-F-3169901-13214-20251030', 6990, '2025-11-17 17:46:40', '2025-11-19 17:28:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6091, 84, 'MG-F-3169901-23110-20251105', 6990, '2025-11-17 17:56:24', '2025-11-17 17:56:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6092, 85, 'MG-F-3146255-14132-20250809', 4625, '2025-11-19 12:02:53', '2025-11-19 12:11:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6093, 86, 'MG-F-3165578-13213-20251031', 6557, '2025-11-19 12:05:34', '2025-11-19 12:08:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6094, 87, NULL, 7070, '2025-11-19 12:14:54', '2025-11-19 12:19:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6095, 88, 'MG-F-3111903-13214-20251116', 1190, '2025-11-19 12:22:49', '2025-11-24 11:21:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6096, 89, NULL, 3670, '2025-11-19 16:10:11', '2025-11-19 16:10:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6097, 90, 'MG-F-3136702-13321-20251021', 3670, '2025-11-19 16:14:09', '2025-11-19 16:15:03');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6098, 91, 'MG-F-3136702-13321-20250926', 3670, '2025-11-19 16:16:46', '2025-11-19 16:17:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6099, 92, 'MG-F-3136702-13321-20250926', 3670, '2025-11-19 16:16:46', '2025-11-19 16:16:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6100, 93, 'MG-F-3136702-13321-20250904', 3670, '2025-11-19 16:19:51', '2025-11-19 16:21:26');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6101, 94, 'MG-F-3136702-13321-20250812', 3670, '2025-11-19 16:24:05', '2025-11-19 16:25:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6102, 95, 'MG-F-3136702-13321-20250801', 3670, '2025-11-19 16:46:58', '2025-11-19 16:52:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6103, 96, 'MG-F-3136702-13321-20250715', 3670, '2025-11-19 16:59:06', '2025-11-19 17:06:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6104, 97, 'MG-F-3136702-13321-20250705', 3670, '2025-11-19 17:13:38', '2025-11-19 17:15:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6105, 98, 'MG-F-3136702-13321-20250613', 3670, '2025-11-19 17:21:07', '2025-11-19 17:22:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6106, 99, NULL, 1190, '2025-11-24 14:01:04', '2025-11-24 14:01:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6107, 100, 'MG-F-3101706-13214-20251119', 170, '2025-11-28 10:55:30', '2025-11-28 10:57:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6108, 101, 'MG-F-3104502-14132-20251010', 450, '2025-11-28 11:04:35', '2025-11-28 11:07:40');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6109, 102, 'MG-F-3103405-14120-20251119', 340, '2025-11-28 11:11:43', '2025-12-01 11:02:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6110, 103, 'MG-F-3170404-13215-20251117', 7040, '2025-11-28 11:17:14', '2025-11-28 11:22:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6111, 104, 'MG-F-3102308-13214-20251128', 230, '2025-12-01 12:02:57', '2025-12-01 14:42:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6112, 105, 'MG-F-3139300-13214-20251127', 3930, '2025-12-01 12:07:50', '2026-01-05 14:24:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6113, 106, NULL, 825, '2025-12-01 14:15:39', '2025-12-01 14:19:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6114, 107, NULL, 230, '2025-12-01 14:23:37', '2025-12-01 14:39:29');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6115, 108, 'MG-F-3138203-13215-20251108', 3820, '2025-12-01 17:03:33', '2025-12-01 17:04:55');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6116, 109, NULL, 5500, '2025-12-01 18:20:00', '2025-12-01 18:20:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6117, 110, 'MG-F-3109303-14120-20251006', 930, '2025-12-02 17:13:20', '2025-12-02 17:16:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6118, 111, NULL, 6460, '2025-12-03 12:01:47', '2025-12-03 12:02:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6119, 112, NULL, 7040, '2025-12-03 12:30:22', '2025-12-03 12:31:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6120, 113, 'MG-F-3164605-14132-20250930', 6460, '2025-12-03 12:33:54', '2025-12-03 12:34:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6121, 114, 'MG-F-3136702-11110-20251004', 3670, '2025-12-03 12:48:27', '2025-12-03 12:49:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6122, 115, 'MG-F-3111200-13214-20251201', 1120, '2025-12-03 12:51:31', '2025-12-09 11:23:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6123, 116, 'MG-F-3122454-13214-20251126', 2245, '2025-12-03 13:00:36', '2026-01-05 14:15:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6124, 117, 'MG-F-3154309-14110-20251201', 5430, '2025-12-03 13:05:20', '2025-12-11 11:20:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6125, 45, 'MG-F-3155009-13215-20251105', 5500, '2025-12-03 16:00:39', '2025-12-03 16:00:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6126, 55, 'MG-F-3152808-13215-20251031', 5280, '2025-12-03 16:03:17', '2025-12-03 16:03:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6127, 118, 'MG-F-3146206-13214-20251125', 4620, '2025-12-04 11:06:34', '2026-01-05 17:15:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6128, 119, 'MG-F-3149507-13214-20251202', 4950, '2025-12-04 11:18:43', '2026-01-05 13:45:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6129, 120, 'MG-F-3146305-13214-20251203', 4630, '2025-12-08 17:42:13', '2026-01-05 13:20:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6130, 121, 'MG-F-3136009-13214-20251205', 3600, '2025-12-09 10:53:55', '2026-01-05 13:12:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6131, 122, 'MG-F-3113701-13214-20251205', 1370, '2025-12-09 10:59:58', '2025-12-09 11:00:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6132, 123, 'MG-F-3129657-12200-20251123', 2965, '2025-12-09 11:03:01', '2025-12-09 11:03:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6133, 124, 'MG-F-3139409-13214-20251202', 3940, '2025-12-09 11:07:53', '2025-12-09 11:07:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6134, 125, 'MG-F-3146255-13214-20251129', 4625, '2025-12-09 11:12:12', '2025-12-09 11:13:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6135, 126, 'MG-F-3146255-14132-20250930', 4625, '2025-12-09 11:15:42', '2025-12-09 11:19:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6136, 127, 'MG-F-3116902-11314-20250920', 1690, '2025-12-10 15:26:17', '2025-12-10 15:27:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6137, 128, 'MG-F-3138906-13214-20251206', 3890, '2025-12-10 15:31:42', '2025-12-23 14:28:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6138, 129, 'MG-F-3161106-13214-20251205', 6110, '2025-12-10 15:42:52', '2025-12-15 11:36:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6139, 130, 'MG-F-3115201-13213-20251128', 1520, '2025-12-10 15:48:55', '2025-12-10 15:49:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6140, 131, 'MG-F-3103405-12300-20251207', 340, '2025-12-10 15:52:27', '2025-12-10 15:53:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6141, 132, 'MG-F-3133808-11321-20251210', 3380, '2025-12-10 15:56:19', '2025-12-10 15:56:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6142, 133, NULL, 4870, '2025-12-11 11:31:11', '2025-12-11 11:33:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6143, 134, 'MG-F-3110509-13215-20251211', 1050, '2025-12-12 11:08:17', '2025-12-12 11:14:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6144, 135, 'MG-F-3147105-13215-20251118', 4710, '2025-12-12 11:10:27', '2025-12-12 11:11:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6145, 136, 'MG-F-3113701-13214-20251210', 1370, '2025-12-12 11:14:42', '2025-12-15 11:58:40');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6146, 137, 'MG-F-3164407-13215-20251202', 6440, '2025-12-12 11:16:25', '2025-12-12 11:19:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6147, 138, 'MG-F-3148707-13214-20251206', 4870, '2025-12-12 11:19:21', '2025-12-12 11:22:28');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6148, 139, 'MG-F-3136306-13214-20251118', 3630, '2025-12-12 17:47:19', '2025-12-12 17:48:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6149, 140, NULL, 400, '2025-12-15 11:42:32', '2025-12-15 11:51:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6150, 141, 'MG-F-3101706-13214-20251205', 170, '2025-12-15 12:23:01', '2025-12-15 12:25:16');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6151, 142, 'MG-F-3117836-13214-20251215', 1783, '2025-12-16 18:13:14', '2025-12-16 18:37:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6152, 143, 'MG-F-3165578-13214-20251213', 6557, '2025-12-16 18:40:52', '2025-12-16 18:46:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6153, 144, 'MG-F-3152204-13215-20251214', 5220, '2025-12-16 18:51:35', '2025-12-16 18:55:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6154, 145, 'Erosão Continental - Boçorocas', 5900, '2025-12-16 19:22:30', '2025-12-16 19:50:57');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6155, 146, 'MG-F-3124906-13214-20251215', 2490, '2025-12-16 19:53:27', '2025-12-16 19:57:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6156, 147, 'MG-F-3131703-13214-20251212', 3170, '2025-12-16 19:58:51', '2025-12-30 16:47:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6157, 148, 'MG-F-3131802-13214-20251213', 3180, '2025-12-16 20:04:11', '2025-12-16 20:06:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6158, 149, 'MG-F-3162609-13214-20251214', 6260, '2025-12-16 20:08:11', '2025-12-16 20:11:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6159, 150, 'MG-F-3108255-13214-20251125', 825, '2025-12-17 13:07:12', '2025-12-17 13:58:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6160, 151, 'MG-F-3155405-13214-20251217', 5540, '2025-12-17 16:55:31', '2025-12-30 17:53:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6161, 152, 'MG-F-3147105-13214-20251212', 4710, '2025-12-17 17:03:48', '2025-12-17 17:03:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6162, 153, 'MG-F-3115300-13214-20251216', 1530, '2025-12-18 13:50:12', '2025-12-18 13:54:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6163, 154, 'MG-F-3104601-13214-20251217', 460, '2025-12-18 15:36:58', '2025-12-18 15:40:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6164, 155, 'MG-F-3129657-12200-20251217', 2965, '2025-12-18 15:45:01', '2025-12-18 15:45:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6165, 156, 'MG-F-3136702-13214-20251216', 3670, '2025-12-18 18:45:14', '2025-12-30 17:49:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6166, 157, 'MG-F-3123528-13214-20251214', 2352, '2025-12-18 18:52:52', '2026-01-05 17:07:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6167, 158, 'MG-F-3133600-13215-20251217', 3360, '2025-12-18 19:23:11', '2025-12-18 19:26:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6168, 159, 'MG-F-3172004-13214-20251217', 7200, '2025-12-18 19:28:06', '2025-12-18 19:29:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6169, 160, 'MG-F-3136306-13214-20251208', 3630, '2025-12-18 19:32:57', '2025-12-18 19:33:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6171, 162, NULL, 5670, '2025-12-19 17:39:43', '2025-12-19 17:39:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6172, 163, 'MG-F-3111507-13214-20251217', 1150, '2025-12-19 18:45:09', '2025-12-30 14:48:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6173, 164, NULL, 1140, '2025-12-22 12:16:33', '2025-12-22 12:17:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6174, 165, 'MG-F-3171303-13214-20251217', 7130, '2025-12-22 12:21:55', '2025-12-30 17:33:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6175, 166, NULL, 1150, '2025-12-22 12:33:55', '2025-12-22 12:38:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6176, 167, 'MG-F-3148301-13214-20251217', 4830, '2025-12-22 15:35:30', '2025-12-30 17:03:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6177, 168, NULL, 5670, '2025-12-22 16:15:41', '2025-12-22 16:15:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6178, 169, NULL, 6860, '2025-12-22 17:37:21', '2025-12-22 17:40:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6179, 170, 'MG-F-3139409-13214-20251217', 3940, '2025-12-22 17:43:20', '2025-12-22 17:44:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6180, 171, 'MG-F-3152808-13214-20251217', 5280, '2025-12-23 11:37:11', '2025-12-23 11:38:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6181, 172, 'MG-F-3170404-13214-20251130', 7040, '2025-12-23 11:45:46', '2025-12-23 11:49:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6182, 173, 'MG-F-3126901-13214-20251215', 2690, '2025-12-23 11:51:39', '2025-12-23 11:51:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6183, 174, 'MG-F-3140902-13214-20251216', 4090, '2025-12-23 11:53:51', '2025-12-30 14:55:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6184, 175, 'MG-F-3114709-13214-20251219', 1470, '2025-12-23 18:08:42', '2026-01-05 13:06:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6185, 176, 'MG-F-3161700-13215-20251226', 6170, '2025-12-29 11:34:18', '2025-12-30 11:59:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6186, 177, 'MG-F-3111150-12200-20251217', 1115, '2025-12-29 11:42:46', '2025-12-30 14:36:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6187, 178, NULL, 6520, '2025-12-29 15:06:00', '2025-12-30 17:17:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6188, 179, NULL, 4710, '2026-01-05 12:49:36', '2026-01-05 12:50:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6189, 180, 'MG-F-3168705-13215-20260101', 6870, '2026-01-05 12:52:49', '2026-01-05 12:55:05');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6190, 181, NULL, 4015, '2026-01-05 12:57:24', '2026-01-12 12:13:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6191, 182, 'MG-F-3102407-13215-20251230', 240, '2026-01-05 13:06:16', '2026-01-05 13:22:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6192, 183, 'MG-F-3168606-13214-20251205', 6860, '2026-01-05 13:26:30', '2026-01-05 13:29:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6193, 184, 'MG-F-3101607-12300-20251231', 160, '2026-01-05 16:03:46', '2026-01-05 16:04:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6194, 185, 'MG-F-3164407-12100-20260102', 6440, '2026-01-05 18:29:44', '2026-01-05 18:31:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6195, 186, 'MG-F-3147907-13214-20251231', 4790, '2026-01-06 16:07:01', '2026-01-12 12:19:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6196, 187, NULL, 3130, '2026-01-06 18:17:06', '2026-01-06 18:17:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6197, 188, 'MG-F-3154309-13214-20260105', 5430, '2026-01-07 11:34:20', '2026-01-09 11:52:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6198, 189, 'MG-F-3101706-13214-20260105', 170, '2026-01-07 11:40:19', '2026-01-14 17:41:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6199, 190, 'MG-F-3106309-13214-20260105', 630, '2026-01-07 12:18:58', '2026-01-07 12:20:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6200, 191, NULL, 50, '2026-01-07 16:19:08', '2026-01-09 16:44:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6201, 192, NULL, 4535, '2026-01-12 11:29:59', '2026-01-12 11:36:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6202, 193, 'MG-F-3136108-11321-20260106', 3610, '2026-01-13 11:45:01', '2026-01-13 11:49:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6203, 194, 'MG-F-3169356-13214-20260105', 6935, '2026-01-14 11:05:17', '2026-01-15 14:37:59');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6204, 195, 'MG-F-3122470-13213-20260112', 2247, '2026-01-14 11:26:03', '2026-01-14 11:30:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6205, 196, NULL, 3940, '2026-01-14 12:04:43', '2026-01-14 12:06:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6206, 197, NULL, 2247, '2026-01-14 12:20:48', '2026-01-14 12:26:10');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6207, 198, 'MG-F-3150570-13215-20260112', 5057, '2026-01-14 17:46:11', '2026-01-16 12:08:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6208, 199, 'MG-F-3170404-13214-20260113', 7040, '2026-01-14 17:53:30', '2026-01-14 17:55:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6209, 200, 'MG-F-3112604-13214-20260106', 1260, '2026-01-15 11:01:30', '2026-01-19 12:04:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6210, 201, NULL, 2860, '2026-01-15 11:18:42', '2026-01-15 11:19:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6211, 202, NULL, 160, '2026-01-16 17:06:44', '2026-01-16 17:07:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6213, 205, NULL, 5250, '2026-01-19 10:58:52', '2026-01-19 10:58:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6214, 206, NULL, 5250, '2026-01-19 10:59:07', '2026-01-19 10:59:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6215, 207, 'MG-F-3127107-13215-20260112', 2710, '2026-01-19 12:42:19', '2026-01-19 12:44:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6216, 208, NULL, 6860, '2026-01-20 12:46:05', '2026-01-20 12:48:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6217, 209, 'MG-F-3111408-13215-20260115', 1140, '2026-01-20 12:50:40', '2026-01-20 12:51:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6218, 210, NULL, 3670, '2026-01-20 13:25:33', '2026-01-20 13:30:59');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6219, 211, 'MG-F-3168606-13214-20260107', 6860, '2026-01-20 16:35:23', '2026-01-20 16:38:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6220, 212, 'MG-F-3143401-12100-20260119', 4340, '2026-01-20 16:40:02', '2026-01-20 16:40:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6221, 213, 'MG-F-3127107-13214-20260117', 2710, '2026-01-20 16:46:09', '2026-01-20 16:47:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6222, 214, 'MG-F-3133808-13213-20260113', 3380, '2026-01-21 11:14:00', '2026-01-21 11:18:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6223, 215, 'MG-F-3147006-13214-20251223', 4700, '2026-01-21 14:06:49', '2026-01-21 14:08:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6224, 216, 'MG-F-3110509-13215-20260119', 1050, '2026-01-21 14:11:05', '2026-01-21 14:53:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6225, 217, 'MG-F-3150000-13214-20260121', 5000, '2026-01-22 17:09:47', '2026-01-23 15:51:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6226, 218, 'MG-F-3168705-13120-20260119', 6870, '2026-01-22 18:46:42', '2026-01-22 18:48:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6227, 219, 'MG-F-3123304-13214-20260120', 2330, '2026-01-22 18:50:19', '2026-01-27 18:39:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6228, 220, NULL, 7040, '2026-01-23 11:03:33', '2026-01-23 11:04:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6229, 221, 'MG-F-3129657-12200-20260121', 2965, '2026-01-23 11:13:40', '2026-01-23 11:14:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6230, 222, 'MG-F-3168606-13214-20260121', 6860, '2026-01-23 11:22:00', '2026-01-28 16:06:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6231, 223, NULL, 1615, '2026-01-23 15:58:38', '2026-01-23 15:59:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6232, 224, 'MG-F-3170750-13214-20260121', 7075, '2026-01-23 17:02:50', '2026-01-30 18:25:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6233, 225, 'MG-F-3170800-13214-20260124', 7080, '2026-01-25 18:01:59', '2026-01-25 18:06:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6234, 226, 'MG-F-3140001-13214-20260124', 4000, '2026-01-26 12:03:09', '2026-01-26 12:03:29');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6235, 227, 'MG-F-3115508-13214-20260124', 1550, '2026-01-26 12:05:52', '2026-01-26 12:07:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6236, 228, NULL, 4430, '2026-01-26 12:10:06', '2026-01-26 12:11:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6237, 229, NULL, 5250, '2026-01-26 12:18:03', '2026-01-26 12:21:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6238, 230, 'MG-F-3136306-13214-20260123', 3630, '2026-01-26 12:25:34', '2026-02-05 11:35:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6239, 231, 'MG-F-3165206-13212-20251229', 6520, '2026-01-26 12:43:02', '2026-01-26 13:24:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6240, 232, 'MG-F-3139003-13214-20260119', 3900, '2026-01-26 12:45:36', '2026-01-26 12:48:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6241, 233, NULL, 3380, '2026-01-26 15:44:36', '2026-01-26 15:46:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6242, 23, 'MG-F-3152808-14120-20251015', 5280, '2026-01-26 16:11:01', '2026-01-26 16:15:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6243, 234, 'MG-F-3156700-13214-20251216', 5670, '2026-01-26 17:35:34', '2026-01-27 11:01:57');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6244, 235, 'MG-F-3126109-13214-20260123', 2610, '2026-01-26 17:51:53', '2026-01-26 17:54:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6245, 236, NULL, 1260, '2026-01-27 10:51:02', '2026-01-27 10:57:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6246, 237, 'MG-F-3154457-12300-20260125', 5445, '2026-01-27 11:03:46', '2026-01-27 11:05:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6247, 238, 'MG-F-3108206-13214-20260125', 820, '2026-01-27 11:07:34', '2026-02-04 12:12:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6248, 239, 'MG-F-3146255-13214-20260126', 4625, '2026-01-27 11:15:48', '2026-01-27 11:15:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6249, 240, 'MG-F-3115458-13214-20260121', 1545, '2026-01-27 11:18:30', '2026-01-27 11:19:03');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6250, 241, 'MG-F-3132404-13214-20260124', 3240, '2026-01-27 12:19:29', '2026-01-27 12:23:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6251, 242, 'MG-F-3167707-13214-20260121', 6770, '2026-01-27 13:39:29', '2026-01-28 15:54:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6252, 243, 'MG-F-3104700-13214-20260122', 470, '2026-01-27 13:42:07', '2026-01-27 13:45:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6253, 244, 'MG-F-3123601-13214-20260125', 2360, '2026-01-27 16:00:46', '2026-01-29 17:19:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6254, 245, 'MG-F-3120508-12200-20260126', 2050, '2026-01-27 17:17:00', '2026-01-27 17:17:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6255, 246, 'MG-F-3138609-11321-20260125', 3860, '2026-01-27 18:37:09', '2026-01-27 18:40:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6256, 247, 'MG-F-3152006-13214-20260120', 5200, '2026-01-28 16:08:39', '2026-01-30 18:05:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6257, 248, 'MG-F-3152402-13214-20260119', 5240, '2026-01-28 16:41:10', '2026-01-28 16:42:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6258, 249, 'MG-F-3103504-13215-20251130', 350, '2026-01-28 16:47:50', '2026-01-28 16:50:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6259, 250, NULL, 5920, '2026-01-28 17:10:57', '2026-01-28 17:10:57');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6260, 251, NULL, 5920, '2026-01-28 17:57:16', '2026-01-28 17:57:16');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6261, 252, NULL, 5920, '2026-01-28 18:05:50', '2026-01-28 18:05:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6262, 253, NULL, 7170, '2026-01-29 11:41:08', '2026-01-30 16:46:56');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6263, 254, 'MG-F-3170404-13214-20260123', 7040, '2026-01-29 11:46:29', '2026-01-30 17:56:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6264, 255, 'MG-F-3132305-13214-20260120', 3230, '2026-01-29 16:02:01', '2026-01-30 18:33:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6266, 256, 'MG-F-3153202-13214-20260123', 5320, '2026-01-29 16:29:31', '2026-02-09 12:55:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6267, 257, 'MG-F-3154457-13214-20260125', 5445, '2026-01-29 16:47:55', '2026-01-29 16:53:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6268, 258, 'MG-F-3147105-13214-20260122', 4710, '2026-01-29 16:57:39', '2026-01-29 16:57:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6269, 259, 'MG-F-3153400-13214-20260118', 5340, '2026-01-29 17:21:58', '2026-01-29 17:23:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6270, 260, NULL, 4620, '2026-01-30 11:27:56', '2026-01-30 11:27:56');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6271, 261, 'MG-F-3146206-13214-20260121', 4620, '2026-01-30 11:28:56', '2026-01-30 11:36:29');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6272, 262, 'MG-F-3126802-12300-20260121', 2680, '2026-01-30 16:43:31', '2026-01-30 16:45:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6273, 263, 'MG-F-3161106-12100-20260127-DELETADO', 6110, '2026-01-30 17:05:26', '2026-01-30 17:15:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6274, 264, 'MG-F-3108552-13214-20260125', 855, '2026-01-30 17:28:29', '2026-01-30 17:31:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6275, 265, 'MG-F-3147105-13214-20260126', 4710, '2026-01-30 17:34:39', '2026-01-30 17:35:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6276, 266, 'MG-F-3112109-13214-20260123', 1210, '2026-01-30 17:36:47', '2026-01-30 17:40:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6277, 267, 'MG-F-3138708-12100-20260128', 3870, '2026-01-30 17:43:47', '2026-01-30 17:44:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6278, 268, 'MG-F-3139201-13214-20260121', 3920, '2026-02-02 12:56:24', '2026-02-02 13:01:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6279, 269, 'MG-F-3101607-13214-20260130', 160, '2026-02-02 13:28:43', '2026-02-02 13:28:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6280, 270, 'MG-F-3109451-13214-20260126', 945, '2026-02-02 13:30:19', '2026-02-02 13:32:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6281, 271, NULL, 2965, '2026-02-02 13:33:17', '2026-02-02 13:34:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6282, 272, NULL, 160, '2026-02-02 18:45:15', '2026-02-02 18:45:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6283, 273, 'MG-F-3109600-13214-20260131', 960, '2026-02-02 18:54:25', '2026-02-02 18:56:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6284, 274, 'MG-F-3101409-12200-20260128', 140, '2026-02-03 14:05:19', '2026-02-03 14:10:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6285, 275, NULL, 3652, '2026-02-03 14:12:50', '2026-02-03 18:00:28');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6286, 276, 'MG-F-3116902-12200-20260119', 1690, '2026-02-03 18:20:13', '2026-02-03 18:20:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6287, 277, 'MG-F-3142007-13214-20260202', 4200, '2026-02-03 18:22:19', '2026-02-03 18:23:01');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6288, 278, 'MG-F-3126802-13214-20260121', 2680, '2026-02-03 18:39:22', '2026-02-03 18:40:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6289, 279, 'MG-F-3133808-13215-20260202', 3380, '2026-02-03 18:47:15', '2026-02-03 18:49:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6290, 280, 'MG-F-3115607-13214-20260125', 1560, '2026-02-03 18:52:24', '2026-02-03 18:53:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6291, 281, NULL, 6110, '2026-02-04 11:10:30', '2026-02-04 11:15:40');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6292, 282, 'MG-F-3159209-13214-20260126', 5920, '2026-02-04 12:27:14', '2026-02-04 12:29:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6293, 283, NULL, 3630, '2026-02-02 19:42:23', '2026-02-02 19:42:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6294, 284, 'MG-F-3147006-24100-20260102', 4700, '2026-02-04 13:16:12', '2026-02-04 13:18:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6295, 285, 'MG-F-3151206-12100-20260122', 5120, '2026-02-04 13:20:37', '2026-02-04 13:21:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6296, 286, 'MG-F-3116159-13214-20260202', 1615, '2026-02-04 13:27:05', '2026-02-04 13:28:05');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6297, 287, 'MG-F-3132404-13214-20260130', 3240, '2026-02-04 13:59:37', '2026-02-04 14:04:08');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6298, 288, NULL, 3630, '2026-02-04 14:22:06', '2026-02-04 14:22:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6299, 289, 'MG-F-3103504-13214-20260128', 350, '2026-02-04 16:06:09', '2026-02-04 16:07:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6300, 290, 'MG-F-3104304-13214-20260130', 430, '2026-02-04 16:08:58', '2026-02-13 15:41:55');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6301, 291, 'MG-F-3147006-12100-20260129', 4700, '2026-02-04 16:12:41', '2026-02-04 16:15:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6302, 292, 'MG-F-3161106-12100-20260127', 6110, '2026-02-04 17:28:40', '2026-02-04 17:40:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6303, 293, 'MG-F-3108552-13214-20260130', 855, '2026-02-05 12:00:17', '2026-02-05 12:00:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6304, 294, 'MG-F-3117836-13214-20260130', 1783, '2026-02-05 12:02:46', '2026-02-05 12:09:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6305, 295, NULL, 4055, '2026-02-06 11:13:02', '2026-02-06 11:21:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6306, 296, 'MG-F-3167004-13214-20260203', 6700, '2026-02-06 12:56:05', '2026-02-06 13:01:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6307, 297, 'MG-F-3164407-12100-20260206', 6440, '2026-02-09 15:52:05', '2026-02-09 15:59:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6308, 298, 'MG-F-3113107-13214-20260206', 1310, '2026-02-09 18:23:29', '2026-02-10 11:14:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6309, 299, 'MG-F-3113107-13214-20260206', 1310, '2026-02-09 18:23:29', '2026-02-11 11:49:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6310, 300, NULL, 3753, '2026-02-10 10:48:05', '2026-02-10 11:01:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6311, 301, NULL, 3753, '2026-02-10 10:55:38', '2026-02-10 10:55:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6312, 302, 'MG-F-3110905-13214-20260128', 1090, '2026-02-10 11:03:34', '2026-02-10 11:08:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6313, 303, NULL, 5140, '2026-02-10 11:16:43', '2026-02-10 11:18:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6314, 304, 'MG-F-3105509-13214-20260205', 550, '2026-02-10 11:35:45', '2026-02-10 11:40:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6315, 305, 'MG-F-3133808-13214-20260205', 3380, '2026-02-10 11:43:18', '2026-02-10 11:44:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6316, 306, 'MG-F-3103405-13214-20260206', 340, '2026-02-10 11:47:19', '2026-02-10 11:50:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6317, 307, 'MG-F-3149507-13214-20260204', 4950, '2026-02-10 11:53:42', '2026-02-10 11:56:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6318, 308, 'MG-F-3145307-13214-20260206', 4530, '2026-02-10 12:02:33', '2026-02-10 12:05:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6319, 309, 'MG-F-3110806-13214-20260206', 1080, '2026-02-10 12:07:38', '2026-02-10 12:10:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6320, 310, 'MG-F-3112703-13214-20260130', 1270, '2026-02-10 12:17:18', '2026-02-10 12:18:08');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6321, 311, 'MG-F-3115300-13214-20260202', 1530, '2026-02-10 12:20:49', '2026-02-10 12:22:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6322, 312, 'MG-F-3138203-13215-20260127', 3820, '2026-02-10 12:24:27', '2026-02-10 12:24:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6323, 313, 'MG-F-3115508-13214-20260205', 1550, '2026-02-10 12:26:17', '2026-02-10 12:27:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6324, 314, NULL, 4390, '2026-02-10 12:29:03', '2026-02-10 12:29:03');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6325, 315, 'MG-F-3146503-11340-20260202', 4650, '2026-02-10 12:29:17', '2026-02-10 12:30:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6326, 316, 'MG-F-3170479-12300-20260125', 7047, '2026-02-10 12:33:30', '2026-02-10 12:34:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6327, 317, 'MG-F-3168507-12300-20260203', 6850, '2026-02-10 12:36:27', '2026-02-10 12:39:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6328, 318, NULL, 2490, '2026-02-10 17:21:46', '2026-02-10 17:21:46');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6329, 319, NULL, 2490, '2026-02-10 17:22:57', '2026-02-10 17:22:57');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6330, 320, NULL, 2490, '2026-02-10 17:23:19', '2026-02-10 17:23:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6331, 321, NULL, 4820, '2026-02-11 10:25:18', '2026-02-23 15:30:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6332, 322, 'MG-F-3124906-13214-20260210', 2490, '2026-02-11 10:27:43', '2026-02-11 11:53:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6333, 323, 'MG-F-3107406-11321-20260210', 740, '2026-02-11 10:41:57', '2026-02-11 10:44:08');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6334, 324, 'MG-F-3132404-13214-20260206', 3240, '2026-02-11 10:45:53', '2026-02-11 10:50:26');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6335, 325, 'MG-F-3137007-13214-20260205', 3700, '2026-02-11 10:51:52', '2026-02-11 11:21:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6336, 326, 'MG-F-3172202-12200-20260208', 7220, '2026-02-11 11:23:49', '2026-02-11 11:23:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6337, 327, NULL, 1310, '2026-02-11 18:31:00', '2026-02-11 18:36:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6338, 328, 'MG-F-3132404-12100-20260209', 3240, '2026-02-12 10:29:52', '2026-02-25 18:10:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6339, 329, 'MG-F-3143906-13214-20260209', 4390, '2026-02-12 10:38:13', '2026-04-24 11:39:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6340, 330, 'MG-F-3147303-13214-20260201', 4730, '2026-02-12 10:43:18', '2026-02-12 10:43:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6341, 331, 'MG-F-3104007-13215-20260209', 400, '2026-02-13 10:48:00', '2026-02-27 11:28:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6342, 332, 'MG-F-3171600-13214-20260202', 7160, '2026-02-13 10:51:41', '2026-02-13 10:55:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6343, 333, 'MG-F-3148004-13214-20260207', 4800, '2026-02-13 10:57:13', '2026-02-13 10:57:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6344, 334, 'MG-F-3129707-13214-20260208', 2970, '2026-02-13 10:59:31', '2026-02-13 11:00:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6345, 335, 'MG-F-3166956-13214-20260210', 6695, '2026-02-13 11:01:50', '2026-02-13 11:06:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6346, 336, 'MG-F-3129103-13214-20260204', 2910, '2026-02-15 13:03:15', '2026-02-15 13:10:52');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6347, 337, 'MG-F-3134202-13214-20260208', 3420, '2026-02-15 13:15:12', '2026-02-15 13:20:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6348, 338, 'MG-F-3147006-13214-20260124', 4700, '2026-02-15 19:03:04', '2026-02-15 19:08:27');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6349, 339, 'MG-F-3134608-13214-20260208', 3460, '2026-02-15 19:10:17', '2026-02-24 18:42:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6350, 340, 'MG-F-3170057-13214-20260209', 7005, '2026-02-15 19:15:08', '2026-02-15 19:19:03');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6351, 341, NULL, 4900, '2026-02-15 19:20:31', '2026-02-15 19:23:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6352, 342, 'MG-F-3139003-13214-20260209', 3900, '2026-02-19 10:59:12', '2026-02-19 11:02:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6353, 343, NULL, 3900, '2026-02-19 17:42:27', '2026-02-19 17:45:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6354, 344, 'MG-F-3146206-13214-20260211', 4620, '2026-02-20 14:19:20', '2026-02-20 14:23:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6355, 345, 'MG-F-3152600-13214-20260206', 5260, '2026-02-20 14:25:32', '2026-02-20 14:25:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6356, 346, 'MG-F-3139409-13214-20260219', 3940, '2026-02-20 14:26:49', '2026-02-20 14:28:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6357, 347, 'MG-F-3158805-13215-20260218', 5880, '2026-02-20 14:29:35', '2026-02-20 14:30:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6358, 348, 'MG-F-3167806-13215-20260217', 6780, '2026-02-20 14:31:51', '2026-02-20 14:31:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6359, 349, 'MG-F-3170479-13214-20260219', 7047, '2026-02-22 18:21:46', '2026-03-05 11:42:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6361, 351, 'MG-F-3136702-13214-20260223', 3670, '2026-02-24 12:50:53', '2026-03-31 11:07:30');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6363, 353, NULL, 6990, '2026-02-24 12:53:47', '2026-02-24 12:53:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6364, 354, NULL, 6990, '2026-02-24 12:54:53', '2026-02-24 12:54:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6365, 355, 'MG-F-3104205-13214-20260123', 420, '2026-02-24 12:58:36', '2026-02-24 13:01:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6366, 356, NULL, 6990, '2026-02-24 10:46:39', '2026-02-24 10:46:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6367, 357, NULL, 6990, '2026-02-24 10:46:39', '2026-02-24 10:46:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6368, 358, NULL, 1310, '2026-02-24 14:04:22', '2026-02-24 14:07:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6369, 359, NULL, 6990, '2026-02-24 17:05:35', '2026-03-18 12:39:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6370, 360, 'MG-F-3121902-13214-20260223', 2190, '2026-02-24 17:41:11', '2026-02-24 20:27:56');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6371, 361, 'MG-F-3107802-13214-20260210', 780, '2026-02-24 17:46:32', '2026-02-24 17:48:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6372, 362, 'MG-F-3149002-13214-20260223', 4900, '2026-02-24 17:52:11', '2026-02-24 17:53:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6373, 363, 'MG-F-3139904-13214-20260223', 3990, '2026-02-24 17:55:42', '2026-02-24 17:58:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6374, 364, 'MG-F-3103405-13214-20260223', 340, '2026-02-24 18:16:17', '2026-03-05 11:32:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6375, 365, 'MG-F-3104700-13214-20260222', 470, '2026-02-24 19:09:45', '2026-03-05 11:39:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6376, 366, 'MG-F-3104205-13214-20260129', 420, '2026-02-24 19:36:46', '2026-02-24 19:38:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6377, 367, 'MG-F-3116902-24100-20260210', 1690, '2026-02-24 19:53:46', '2026-02-24 19:54:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6378, 368, 'MG-F-3143401-12100-20260205', 4340, '2026-02-24 19:56:33', '2026-02-24 20:01:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6379, 369, 'MG-F-3116902-12200-20260127', 1690, '2026-02-24 20:05:04', '2026-02-24 20:05:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6380, 370, 'MG-F-3155900-13214-20260223', 5590, '2026-02-24 20:12:55', '2026-03-20 12:13:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6381, 371, NULL, 205, '2026-02-24 21:12:34', '2026-02-24 21:12:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6382, 372, NULL, 5220, '2026-02-25 10:48:42', '2026-02-25 10:48:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6383, 373, 'MG-F-3102050-13214-20260224', 205, '2026-02-25 11:40:10', '2026-02-25 11:40:10');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6384, 374, 'MG-F-3165701-13214-20260224', 6570, '2026-02-25 17:24:30', '2026-02-26 17:51:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6385, 375, 'MG-F-3152204-13214-20260223', 5220, '2026-02-25 18:18:21', '2026-02-25 18:19:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6386, 376, 'MG-F-3149507-13214-20260224', 4950, '2026-02-25 18:49:00', '2026-02-25 18:54:28');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6387, 377, 'MG-F-3163706-12300-20260224', 6370, '2026-02-25 19:39:37', '2026-02-25 19:41:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6388, 378, 'MG-F-3115300-13214-20260224', 1530, '2026-02-25 19:43:20', '2026-02-28 22:03:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6389, 379, NULL, 4535, '2026-02-25 19:47:07', '2026-02-25 19:49:03');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6390, 380, NULL, 7200, '2026-02-26 11:22:20', '2026-02-28 22:11:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6391, 381, 'MG-F-3140803-13214-20260223', 4080, '2026-02-26 12:44:06', '2026-02-26 16:14:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6392, 382, 'MG-F-3139904-12100-20260223', 3990, '2026-02-27 10:46:00', '2026-02-27 10:49:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6393, 383, 'MG-F-3125002-13214-20260226', 2500, '2026-02-27 10:52:28', '2026-02-27 11:00:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6394, 384, 'MG-F-3134301-13214-20260224', 3430, '2026-02-27 11:03:35', '2026-03-06 20:57:17');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6395, 385, 'MG-F-3170404-13214-20260224', 7040, '2026-02-27 11:07:51', '2026-02-27 11:10:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6396, 386, 'MG-F-3132602-13214-20260226', 3260, '2026-02-27 11:13:50', '2026-02-27 11:18:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6397, 387, 'MG-F-3131208-13214-20260227', 3120, '2026-02-27 22:20:14', '2026-02-28 12:58:40');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6398, 388, 'MG-F-3127602-13214-20260226', 2760, '2026-02-28 13:14:43', '2026-02-28 13:23:26');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6399, 389, 'MG-F-3135076-13214-20260227', 3507, '2026-02-28 22:16:21', '2026-03-06 20:48:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6400, 390, 'MG-F-3110202-13214-20260226', 1020, '2026-02-28 22:18:43', '2026-03-13 13:33:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6401, 391, 'MG-F-3168606-13214-20260227', 6860, '2026-02-28 22:22:16', '2026-03-05 14:19:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6402, 392, 'MG-F-3144003-13214-20260227', 4400, '2026-02-28 22:24:57', '2026-02-28 22:43:42');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6403, 393, 'MG-F-3142007-13214-20260225', 4200, '2026-02-28 22:52:22', '2026-02-28 22:55:16');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6404, 394, 'MG-F-3150604-13214-20260223', 5060, '2026-02-28 22:58:11', '2026-02-28 23:02:33');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6405, 395, 'MG-F-3104601-13214-20260226', 460, '2026-02-28 23:06:06', '2026-02-28 23:09:26');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6406, 396, 'MG-F-3127305-13214-20260224', 2730, '2026-02-28 23:11:02', '2026-02-28 23:14:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6407, 397, 'MG-F-3122108-13214-20260226', 2210, '2026-02-28 23:15:40', '2026-02-28 23:16:16');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6408, 398, 'MG-F-3100609-13214-20260228', 60, '2026-03-01 15:43:55', '2026-03-01 16:54:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6409, 399, 'MG-F-3150109-13214-20260226', 5010, '2026-03-01 15:45:34', '2026-03-01 15:49:28');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6410, 400, 'MG-F-3123809-13214-20260228', 2380, '2026-03-01 15:50:48', '2026-03-01 15:52:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6411, 401, 'MG-F-3124302-13214-20260301', 2430, '2026-03-01 16:40:58', '2026-04-16 13:16:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6412, 402, 'MG-F-3146305-13214-20260228', 4630, '2026-03-01 17:22:33', '2026-03-06 15:40:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6413, 403, 'MG-F-3152204-13214-20260301', 5220, '2026-03-01 19:36:12', '2026-03-01 20:03:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6414, 404, 'MG-F-3102852-13214-20260228', 285, '2026-03-01 20:06:12', '2026-03-31 17:10:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6415, 405, 'MG-F-3141009-13214-20260228', 4100, '2026-03-01 20:15:46', '2026-03-09 12:40:10');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6416, 406, 'MG-F-3145307-13214-20260228', 4530, '2026-03-01 20:17:33', '2026-03-01 20:19:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6417, 407, 'MG-F-3154507-13214-20260302', 5450, '2026-03-02 17:10:11', '2026-03-02 17:10:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6418, 408, 'MG-F-3126604-12100-20260228', 2660, '2026-03-02 17:12:33', '2026-03-02 17:13:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6419, 409, 'MG-F-3150000-13214-20260227', 5000, '2026-03-02 17:16:16', '2026-03-05 11:08:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6420, 410, 'MG-F-3135803-13214-20260302', 3580, '2026-03-02 17:20:28', '2026-03-02 17:23:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6421, 411, 'MG-F-3143906-13214-20260226', 4390, '2026-03-02 17:33:13', '2026-03-02 17:35:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6422, 412, 'MG-F-3165552-13214-20260228', 6555, '2026-03-02 17:39:54', '2026-03-06 20:39:05');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6423, 413, 'MG-F-3158102-13214-20260228', 5810, '2026-03-03 12:33:29', '2026-03-03 12:44:50');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6424, 414, 'MG-F-3128808-13214-20260223', 2880, '2026-03-03 12:58:13', '2026-03-03 14:33:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6425, 415, 'MG-F-3110103-13214-20260223', 1010, '2026-03-03 13:04:30', '2026-03-03 13:10:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6426, 416, 'MG-F-3167301-13214-20260225', 6730, '2026-03-03 13:11:34', '2026-03-03 13:13:53');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6427, 417, 'MG-F-3168051-13214-20260227', 6805, '2026-03-03 13:15:55', '2026-03-03 13:19:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6428, 418, NULL, 6670, '2026-03-03 13:24:27', '2026-03-03 13:30:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6429, 419, 'MG-F-3104809-13214-20260228', 480, '2026-03-03 13:34:15', '2026-03-06 14:48:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6430, 420, 'MG-F-3141504-13214-20260221', 4150, '2026-03-04 10:15:28', '2026-03-06 20:18:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6431, 421, 'MG-F-3155801-13214-20260225', 5580, '2026-03-04 10:20:25', '2026-03-06 20:00:43');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6432, 422, 'MG-F-3162708-13214-20260302', 6270, '2026-03-04 10:22:16', '2026-03-06 20:13:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6433, 423, 'MG-F-3162104-13214-20260224', 6210, '2026-03-04 10:25:20', '2026-03-04 10:27:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6434, 424, 'MG-F-3135050-12300-20260226', 3505, '2026-03-04 10:30:31', '2026-03-04 10:36:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6435, 425, 'MG-F-3136801-13214-20260228', 3680, '2026-03-04 10:43:41', '2026-03-06 20:26:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6436, 426, 'MG-F-3166956-13214-20260301', 6695, '2026-03-04 10:46:51', '2026-03-10 18:22:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6437, 427, 'MG-F-3137304-13214-20260228', 3730, '2026-03-04 11:35:17', '2026-03-17 11:28:39');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6438, 428, 'MG-F-3155405-12100-20260224', 5540, '2026-03-04 11:37:41', '2026-03-05 10:49:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6439, 429, 'MG-F-3146552-13214-20260301', 4655, '2026-03-04 11:40:00', '2026-03-04 11:40:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6440, 430, 'MG-F-3122355-13214-20260223', 2235, '2026-03-04 11:42:09', '2026-03-04 11:42:09');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6441, 431, 'MG-F-3170305-13214-20260303', 7030, '2026-03-04 11:51:18', '2026-03-05 11:07:45');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6442, 432, 'MG-F-3127354-13214-20260228', 2735, '2026-03-04 11:53:13', '2026-03-05 11:13:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6443, 433, 'MG-F-3143302-13214-20260228', 4330, '2026-03-04 12:01:36', '2026-03-04 12:01:36');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6444, 434, 'MG-F-3145059-12100-20260301', 4505, '2026-03-04 12:04:19', '2026-03-04 12:04:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6445, 435, 'MG-F-3138104-13214-20260304', 3810, '2026-03-04 12:09:38', '2026-03-05 13:15:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6446, 436, 'MG-F-3158102-13214-20260210', 5810, '2026-03-04 12:11:38', '2026-03-04 12:11:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6447, 437, 'MG-F-3132305-13214-20260228', 3230, '2026-03-04 12:19:51', '2026-03-04 12:19:51');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6448, 438, 'MG-F-3138906-13214-20260228', 3890, '2026-03-04 12:25:49', '2026-03-05 11:36:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6449, 439, 'MG-F-3124302-13214-20260228', 2430, '2026-03-04 12:50:13', '2026-03-04 12:50:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6450, 440, 'MG-F-3108701-11321-20260127', 870, '2026-03-04 13:00:12', '2026-03-04 13:00:12');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6451, 441, 'MG-F-3157302-13214-20260225', 5730, '2026-03-05 11:27:21', '2026-03-12 19:39:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6452, 442, 'MG-F-3135050-13214-20260226', 3505, '2026-03-05 11:29:16', '2026-03-05 14:04:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6453, 443, 'MG-F-3120300-13214-20260228', 2030, '2026-03-05 12:45:54', '2026-03-12 16:41:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6454, 444, 'MG-F-3140852-13214-20260302', 4085, '2026-03-05 12:48:23', '2026-03-05 13:07:59');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6458, 448, 'MG-F-3107109-13214-20260227', 710, '2026-03-06 19:06:46', '2026-03-06 19:09:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6459, 449, 'MG-F-3146206-12200-20260226', 4620, '2026-03-06 19:16:11', '2026-03-06 19:19:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6460, 450, 'MG-F-3115474-13214-20260305', 1547, '2026-03-06 19:25:37', '2026-03-06 19:33:55');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6461, 451, 'MG-F-3138401-13214-20260226', 3840, '2026-03-09 17:56:49', '2026-03-09 18:03:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6462, 452, 'MG-F-3142908-13214-20260301', 4290, '2026-03-09 18:06:25', '2026-03-09 18:10:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6463, 453, NULL, 4535, '2026-03-09 18:12:14', '2026-03-09 18:21:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6464, 454, 'MG-F-3102506-13214-20260227', 250, '2026-03-10 18:27:09', '2026-03-10 18:31:56');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6465, 455, 'MG-F-3145208-13214-20260226', 4520, '2026-03-10 19:15:29', '2026-03-11 12:19:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6466, 456, 'MG-F-3126505-13214-20260228', 2650, '2026-03-11 16:17:40', '2026-03-11 16:23:20');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6467, 457, 'MG-F-3120870-13214-20260301', 2087, '2026-03-11 16:34:50', '2026-03-11 16:38:00');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6468, 458, 'MG-F-3133907-12300-2026022', 3390, '2026-03-11 17:27:29', '2026-03-11 17:41:57');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6469, 459, 'MG-F-3124708-13214-20260125', 2470, '2026-03-11 17:29:54', '2026-03-11 17:31:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6470, 460, 'MG-F-3156700-13214-20260309', 5670, '2026-03-11 17:53:39', '2026-03-18 19:27:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6471, 461, 'MG-F-3104403-13214-20260225', 440, '2026-03-11 18:05:54', '2026-03-11 18:08:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6472, 462, 'MG-F-3151107-13214-20260228', 5110, '2026-03-11 18:11:57', '2026-03-11 18:14:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6473, 463, 'MG-F-3146255-13214-20260303', 4625, '2026-03-11 18:16:49', '2026-03-11 18:18:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6474, 464, 'MG-F-3108602-13214-20260309', 860, '2026-03-11 18:19:48', '2026-03-11 18:22:41');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6475, 465, 'MG-F-3133808-12300-20260220', 3380, '2026-03-11 18:25:19', '2026-03-11 18:26:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6476, 466, 'MG-F-3155603-13214-20260305', 5560, '2026-03-11 18:50:16', '2026-03-11 18:52:11');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6477, 467, 'MG-F-3148301-13214-20260308', 4830, '2026-03-12 18:51:21', '2026-03-12 18:54:07');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6478, 468, 'MG-F-3100708-13214-20260307', 70, '2026-03-12 20:51:36', '2026-03-18 20:38:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6479, 469, 'MG-F-3105202-13214-20260307', 520, '2026-03-13 11:14:20', '2026-03-13 13:20:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6480, 470, 'MG-F-3100302-13214-20260310', 30, '2026-03-13 13:27:03', '2026-03-18 19:28:59');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6481, 471, 'MG-F-3128402-13214-20260226', 2840, '2026-03-13 18:15:07', '2026-03-13 18:18:06');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6482, 472, 'MG-F-3133907-12300-20260224', 3390, '2026-03-13 18:24:27', '2026-03-13 18:28:19');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6483, 473, 'MG-F-3166204-13214-20260312', 6620, '2026-03-16 11:08:04', '2026-03-16 11:11:31');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6484, 474, 'MG-F-3131703-13214-20260309', 3170, '2026-03-19 10:58:06', '2026-03-19 11:02:26');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6485, 475, 'MG-F-3139300-13214-20260223', 3930, '2026-03-19 17:00:51', '2026-03-19 17:04:05');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6486, 476, 'MG-F-3145059-13214-20260302', 4505, '2026-03-19 18:32:58', '2026-03-19 18:36:16');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6487, 477, 'MG-F-3118304-12300-20260310', 1830, '2026-03-20 11:33:16', '2026-03-20 11:36:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6488, 478, 'MG-F-3146255-13214-20260315', 4625, '2026-03-20 12:46:35', '2026-03-20 13:36:15');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6489, 479, 'MG-F-3100906-13214-20260227', 90, '2026-03-20 17:26:47', '2026-03-20 17:29:14');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6492, 482, 'MG-F-3120607-13214-20260312', 2060, '2026-03-25 16:19:23', '2026-03-25 16:19:23');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6493, 483, 'MG-F-3128907-13214-20260312', 2890, '2026-03-27 20:04:40', '2026-03-27 20:07:55');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6494, 484, 'MG-F-3140704-13214-20260312', 4070, '2026-03-27 20:16:47', '2026-03-27 20:20:04');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6495, 485, 'MG-F-3162401-13214-20260323', 6240, '2026-03-30 10:47:12', '2026-03-30 10:50:08');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6496, 486, 'MG-F-3157609-12100-20260326', 5760, '2026-03-30 10:57:43', '2026-03-30 11:00:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6497, 487, 'MG-F-3108503-13214-20260324', 850, '2026-03-30 11:08:08', '2026-03-30 11:11:38');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6498, 488, 'MG-F-3147501-13214-20260324', 4750, '2026-03-30 11:17:56', '2026-03-30 11:22:02');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6499, 489, 'MG-F-3121605-13214-20260323', 2160, '2026-03-30 11:27:20', '2026-03-30 11:32:22');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6500, 490, 'MG-F-3151909-13214-20260323', 5190, '2026-03-30 11:46:26', '2026-03-30 11:50:18');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6501, 491, 'MG-F-3136009-13214-20260323', 3600, '2026-03-30 12:08:32', '2026-03-30 12:08:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6502, 492, 'MG-F-3141603-13214-20260320', 4160, '2026-03-31 11:12:23', '2026-03-31 11:14:25');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6503, 493, 'MG-F-3130507-13214-20260320', 3050, '2026-03-31 11:20:45', '2026-03-31 11:23:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6504, 494, 'MG-F-3116001-13214-20260320', 1600, '2026-03-31 11:50:00', '2026-03-31 11:53:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6505, 495, 'MG-F-3165552-13214-20260321', 6555, '2026-03-31 12:01:37', '2026-03-31 12:04:24');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6506, 496, 'MG-F-3152402-13215-20260319', 5240, '2026-03-31 12:22:28', '2026-03-31 12:33:32');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6507, 497, 'MG-F-3169703-13214-20260316', 6970, '2026-03-31 12:41:14', '2026-03-31 12:46:21');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6508, 498, 'MG-F-3139409-13214-20260317', 3940, '2026-03-31 13:03:44', '2026-03-31 13:03:44');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6509, 499, 'MG-F-3126752-13214-20260315', 2675, '2026-03-31 13:23:27', '2026-03-31 13:26:08');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6510, 500, 'MG-F-3135001-13214-20260323', 3500, '2026-04-01 18:23:00', '2026-04-01 18:29:13');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6511, 501, 'MG-F-3105707-13214-20260323', 570, '2026-04-01 18:34:49', '2026-04-01 18:37:49');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6512, 502, 'MG-F-3123908-13214-20260321', 2390, '2026-04-01 18:40:30', '2026-04-01 18:42:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6513, 503, 'MG-F-3139201-13214-20260323', 3920, '2026-04-09 10:22:33', '2026-04-09 10:26:58');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6514, 504, 'MG-F-3153103-13214-20260331', 5310, '2026-04-09 10:32:19', '2026-04-09 10:35:54');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6515, 505, 'MG-F-3105509-12300-20260323', 550, '2026-04-09 10:44:27', '2026-04-09 10:47:37');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6516, 506, 'MG-F-3159001-13214-20260222', 5900, '2026-04-09 19:05:33', '2026-04-09 19:05:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6517, 507, 'MG-F-3106200-13214-20260321', 620, '2026-04-09 19:07:03', '2026-04-09 19:07:35');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6518, 508, 'MG-F-3170206-13214-20260408', 7020, '2026-04-10 13:37:38', '2026-04-10 13:38:48');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6519, 509, 'MG-F-3133907-12300-20260408', 3390, '2026-04-16 13:02:12', '2026-04-17 11:08:47');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6520, 510, 'MG-F-3136652-13214-20260410', 3665, '2026-04-17 11:22:34', '2026-04-17 11:22:34');
INSERT INTO `dec_decreto_municipios` (`id`, `entrada_processos_id`, `n_protocolo_fide`, `municipio_id`, `created_at`, `updated_at`) VALUES
	(6522, 511, 'MG-F-3172210-00000-00000000', 7221, '2026-05-06 13:08:36', '2026-05-06 13:08:36');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
