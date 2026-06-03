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

-- Copiando estrutura para tabela dbsdc.dec_desastre_categorias
CREATE TABLE IF NOT EXISTS `dec_desastre_categorias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `informacao` text COLLATE utf8mb4_unicode_ci,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `desastre_grupo_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.dec_desastre_categorias: ~8 rows (aproximadamente)
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(1, 'DANOS HUMANOS', 'Informar a quantidade de mortos, feridos, enfermos, desabrigados, desalojados, desaparecidos e outras pessoas que foram diretamente afetadas pelo desastre, desde que necessitem de auxílio do poder público ou cujos bens materiais tenham sido danificados /destruídos.', '', NULL, NULL, 3);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(2, 'DANOS MATERIAIS', 'Informar a quantidade de instalações de ensino, saúde, uso comercial ou comunitário, unidades habitacionais ou de obras de infraestrutura danificadas ou destruídas pelo desastre.', '', NULL, NULL, 3);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(3, 'DANOS AMBIENTAIS', 'Informar as alterações ocorridas no meio ambiente que comprometeram a qualidade ambiental em decorrência direta dos efeitos do desastre.', '', NULL, NULL, 3);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(8, 'Área com população afetada/Tipo de ocupação', '', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55', 1);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(9, 'Descrição das áreas com população afetada', '', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55', 1);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(10, 'CAUSAS E EFEITOS DO DESASTRE', '', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48', 2);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(11, 'PREJUÍZOS ECONÔMICOS PÚBLICOS', '', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48', 4);
INSERT INTO `dec_desastre_categorias` (`id`, `titulo`, `informacao`, `descricao`, `created_at`, `updated_at`, `desastre_grupo_id`) VALUES
	(12, 'PREJUÍZOS ECONÔMICOS PRIVADOS', '', '', '2025-09-03 20:25:55', '2025-09-03 20:25:55', 4);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
