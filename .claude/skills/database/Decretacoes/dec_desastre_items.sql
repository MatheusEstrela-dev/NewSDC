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

-- Copiando estrutura para tabela dbsdc.dec_desastre_items
CREATE TABLE IF NOT EXISTS `dec_desastre_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `categoria_id` bigint unsigned NOT NULL,
  `titulo` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dec_desastre_items_categoria_id_foreign` (`categoria_id`),
  CONSTRAINT `dec_desastre_items_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `dec_desastre_categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.dec_desastre_items: ~43 rows (aproximadamente)
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Mortos', 'Pessoas que perderam suas vidas em decorrência direta dos efeitos do desastre.', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(2, 1, 'Feridos', 'Pessoas que sofreram lesões em decorrência direta dos efeitos do desastre e necessitam de intervenção médico-hospitalar, materiais e insumos de saúde (medicamentos, médicos, etc.).', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(3, 1, 'Enfermos', 'Pessoas que desenvolveram processos patológicos em decorrência direta dos efeitos do desastre.', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(4, 1, 'Desabrigados', 'Pessoas que necessitam de abrigo público, como habitação temporária, em função de danos ou ameaça de danos causados em decorrência direta dos efeitos do desastre.', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(5, 1, 'Desalojados', 'Pessoas que, em decorrência dos efeitos diretos do desastre, desocuparam seus domicílios, mas não necessitam de abrigo público.', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(6, 1, 'Desaparecidos', 'Pessoas que necessitam ser encontradas, pois, em decorrência direta dos efeitos do', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(7, 1, 'Outros afetados', 'Pessoas afetadas diretamente pelo desastre (excetuando as já informadas acima).', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(8, 2, 'Unidades habitacionais', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(9, 2, 'Instalações públicas de saúde', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(10, 2, 'Instalações públicas de ensino', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(11, 2, 'Instalações públicas prestadoras de outros', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(12, 2, 'Instalações públicas de uso comunitário', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(13, 2, 'Obras de infraestrutura pública', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(14, 3, 'Poluição ou contaminação da água', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(15, 3, 'Poluição ou contaminação do ar', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(16, 3, 'Poluição ou contaminação do solo', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(17, 3, 'Diminuição ou exaurimento hídrico', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(18, 3, 'Incêndios em parques, APA\'s ou APP\'s', '', NULL, NULL);
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(40, 8, 'Residencial', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(41, 8, 'Comercial', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(42, 8, 'Industrial', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(43, 8, 'Agrícola', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(44, 8, 'Pecuária', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(45, 8, 'Extrativismo vegetal', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(46, 8, 'Reserva florestal ou APA', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(47, 8, 'Mineração', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(48, 8, 'Turismo e outras', '', '2025-09-03 20:12:55', '2025-09-03 20:12:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(49, 11, 'Assistência médica, saúde pública e atendimento de emergências médicas', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(50, 11, 'Abastecimento de água potável', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(51, 11, 'Esgoto de águas pluviais e sistema de esgotos sanitários', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(52, 11, 'Sistema de limpeza urbana e de recolhimento e destinação do lixo', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(53, 11, 'Sistema de desinfestação/desinfecção do habitat/controle de pragas e vetores', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(54, 11, 'Geração e distribuição de energia elétrica', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(55, 11, 'Telecomunicações', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(56, 11, 'Transportes locais, regionais e de longo curso', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(57, 11, 'Distribuição de combustíveis, especialmente os de uso doméstico', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(58, 11, 'Segurança pública', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(59, 11, 'Ensino', '', '2025-09-03 20:24:48', '2025-09-03 20:24:48');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(60, 12, 'Agricultura', '', '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(61, 12, 'Pecuária', '', '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(62, 12, 'Indústria', '', '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(63, 12, 'Comércio', '', '2025-09-03 20:25:55', '2025-09-03 20:25:55');
INSERT INTO `dec_desastre_items` (`id`, `categoria_id`, `titulo`, `observacao`, `created_at`, `updated_at`) VALUES
	(64, 12, 'Serviços', '', '2025-09-03 20:25:55', '2025-09-03 20:25:55');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
