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

-- Copiando estrutura para tabela dbsdc.tdap_lotes
CREATE TABLE IF NOT EXISTS `tdap_lotes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ata_id` int NOT NULL COMMENT 'Identificador da Ata',
  `numero` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número Ata',
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do Lote',
  `municipio_id` int NOT NULL COMMENT 'Municipio',
  `prestador_id` int NOT NULL COMMENT 'Prestador',
  `qtd_agua` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Quantidade de Água M3',
  `valor` decimal(10,2) NOT NULL COMMENT 'Valor de Água M3',
  `unidade` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contrato` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tdap_lotes_ata_id_foreign` (`ata_id`),
  KEY `tdap_lotes_municipio_id_foreign` (`municipio_id`),
  KEY `tdap_lotes_prestador_id_foreign` (`prestador_id`)
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_lotes: 45 rows
/*!40000 ALTER TABLE `tdap_lotes` DISABLE KEYS */;
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(55, 1, '06', 'Transporte e distribuição de água potável destinado aos municípios de Indaiabira, Montezuma, Ninheira, São João do Paraíso e Vargem Grande do Rio Pardo.', 0, 8, '94819.00', 37.83, 'Metros Cúbicos', '2025-07-22 18:10:47', '2025-07-22 18:10:47', '9472521/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(54, 1, '05', 'Transporte e distribuição de água potável destinado aos municípios de Salinas, Fruta de Leite, Josenópolis, Novorizonte, Padre Carvalho e Rubelita.', 0, 5, '18270.00', 60.00, 'Metros Cúbicos', '2025-07-22 18:05:04', '2025-07-22 18:05:04', '9472519/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(50, 1, '01', 'Transporte e distribuição de água potável destinado aos municípios de  Francisco Sá, Captão Enéas e Glaucilância.', 0, 11, '24713.00', 39.00, 'Metros Cúbicos', '2025-07-22 15:15:32', '2025-07-22 15:15:32', '9472513/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(51, 1, '02', 'Transporte e distribuição de água potável destinado aos municípios de Mirabela, Coração de Jesus, Lagoa dos Patos, São João da Lagoa e São João do Pacuí.', 0, 5, '23318.00', 60.00, 'Metros Cúbicos', '2025-07-22 17:09:05', '2025-07-22 17:09:05', '9472514/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(52, 1, '03', 'Transporte e distribuição de água potável destinado aos municípios de Ibiaí, Ponto Chique e Santa Fé de Minas.', 0, 5, '7274.00', 60.00, 'Metros Cúbicos', '2025-07-22 17:10:41', '2025-07-22 17:10:41', '9472516/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(53, 1, '04', 'Transporte e distribuição de água potável destinado aos municípios de Brasília de Minas, Luislândia e Patis.', 0, 10, '8434.00', 59.00, 'Metros Cúbicos', '2025-07-22 18:01:02', '2025-07-22 18:01:02', '9472517/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(12, 18, '01', 'Transporte e distribuição de água potável destinado ao município de Araçuaí.', 0, 1, '37548.00', 36.00, 'Metros Cúbicos', '2025-06-26 13:03:45', '2025-06-26 13:03:45', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(13, 18, '02', 'Transporte e distribuição de água potável destinado aos municípios de Berilo, Coronel Murta e Virgem da Lapa.', 0, 2, '29324.40', 39.00, 'Metros Cúbicos', '2025-06-26 13:07:32', '2025-06-26 13:07:32', '9472432/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(14, 18, '03', 'Transporte e distribuição de água potável destinado aos municípios de Almenara e Rubim.', 0, 1, '29047.20', 34.00, 'Metros Cúbicos', '2025-06-26 13:11:00', '2025-06-26 13:11:00', '9472433/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(15, 18, '04', 'Transporte e distribuição de água potável destinado aos municípios de Francisco Badaró e Jenipapo de Minas.', 0, 1, '14170.80', 45.53, 'Metros Cúbicos', '2025-06-26 13:13:48', '2025-06-26 13:13:48', '9472434/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(16, 18, '05', 'Transporte e distribuição de água potável destinado aos municípios de Felisburgo, Jequitinhonha, Joaíma, Palmópolis e Rio Prado.', 0, 3, '67233.60', 54.00, 'Metros Cúbicos', '2025-06-26 13:15:16', '2025-06-26 13:15:16', '9472435/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(17, 18, '06', 'Transporte e distribuição de água potável destinado aos municípios de Comercinho, Itaobim, Itinga, Medina, Monte Formoso e Ponto dos Volantes.', 0, 5, '51416.40', 52.00, 'Metros Cúbicos', '2025-06-26 13:25:16', '2025-06-26 13:25:16', '9472436/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(18, 18, '07', 'Transporte e distribuição de água potável destinado aos municípios de Caraí, Catuji, Itaipé, Novo cruzeiro e Padre Paraíso.', 0, 6, '35061.60', 49.00, 'Metros Cúbicos', '2025-06-26 13:26:44', '2025-06-26 13:26:44', '9472437/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(19, 18, '08', 'Transporte e distribuição de água potável destinado aos municípios de Jacinto, Jordânia, Salto da Divisa, Santa Maria do Salto e Santo Antônio do Jacinto.', 0, 5, '62622.00', 59.00, 'Metros Cúbicos', '2025-06-26 13:27:52', '2025-06-26 13:27:52', '9472440/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(20, 18, '09', 'Transporte e distribuição de\r\nágua potável destinado aos\r\nmunicípios de Chapada do\r\nNorte, José Gonçalves de\r\nMinas, Leme do Prado e\r\nMinas Novas.', 0, 3, '43646.40', 56.25, 'Metros Cúbicos', '2025-06-26 13:29:10', '2025-06-26 13:29:10', '9472441/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(21, 18, '10', 'Transporte e distribuição de água potável destinado aos municípios de Aricanduva, Capelinha, Coluna, Itamarandiba e Senador Modestino Gonçalves.', 0, 5, '70450.80', 55.00, 'Metros Cúbicos', '2025-06-26 13:33:16', '2025-06-26 13:33:16', '9472595/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(22, 18, '11', 'Transporte e distribuição de água potável destinado aos municípios de Carbonita, Turmalina e Veredinha.', 0, 7, '11860.80', 56.00, 'Metros Cúbicos', '2025-06-26 13:36:33', '2025-06-26 13:36:33', '9472453/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(23, 18, '12', 'Transporte e distribuição de água potável destinado aos municípios de Pedra Azul, Águas Vermelhas, Cachoeira do Pajeú, Divisa Alegre.', 0, 8, '24939.60', 54.50, 'Metros Cúbicos', '2025-06-26 13:39:17', '2025-06-26 13:39:17', '9472457/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(24, 18, '13', 'Transporte e distribuição de água potável destinado aos municípios de Bandeira, Divisópolis e Mata Verde.', 0, 8, '11718.00', 60.10, 'Metros Cúbicos', '2025-06-26 13:40:34', '2025-06-26 13:40:34', '9472461/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(25, 18, '14', 'Transporte e distribuição de\r\nágua potável destinado aos\r\nmunicípios de Águas\r\nFormosas, Crisólita, Fronteira\r\ndos Vales, Machacalis, Pavão\r\ne Umburatiba', 0, 3, '28761.60', 68.57, 'Metros Cúbicos', '2025-06-26 13:41:42', '2025-06-26 13:41:42', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(26, 18, '15', 'Transporte e distribuição de água potável destinado aos municípios de Teófilo Otoni, Angelândia, Ladainha, Poté, Setubinha.', 0, 3, '19345.20', 77.66, 'Metros Cúbicos', '2025-06-26 13:43:15', '2025-06-26 13:43:15', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(27, 18, '16', 'Transporte e distribuição de água potável destinado aos municípios de Diamantina, Gouveia e Presidente Kubistschek.', 0, 3, '30189.60', 92.00, 'Metros Cúbicos', '2025-06-26 13:44:25', '2025-06-26 13:44:25', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(28, 18, '17', 'Transporte e distribuição de água potável destinado aos municípios de Monjolos, Morro da Garça e São Gonçalo do Rio Preto.', 0, 3, '15120.00', 90.00, 'Metros Cúbicos', '2025-06-26 13:45:38', '2025-06-26 13:45:38', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(29, 18, '18', 'Transporte e distribuição de água potável destinado aos municípios do de Itambacuri, Franciscópolis, Frei Gaspar.', 0, 3, '15120.00', 83.88, 'Metros Cúbicos', '2025-06-26 13:46:32', '2025-06-26 13:46:32', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(30, 18, '19', 'Transporte e distribuição de água potável destinado aosmunicípios de Capitão Andrade, Cuparaque e Goiabeira.', 0, 3, '15120.00', 83.88, 'Metros Cúbicos', '2025-06-26 13:47:43', '2025-06-26 13:47:43', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(31, 18, '20', 'Transporte e distribuição de água potável destinado aos municípios de Ituêta, Nova Módica, Resplendor, São Geraldo da Piedade e São Geraldo do Baixo.', 0, 3, '28240.80', 74.99, 'Metros Cúbicos', '2025-06-26 13:48:55', '2025-06-26 13:48:55', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(32, 18, '21', 'Transporte e distribuição de água potável destinado aos municípios de Formoso, Bonfinópolis de Minas, Dom Bosco.', 0, 5, '22520.40', 66.07, 'Metros Cúbicos', '2025-06-26 13:51:11', '2025-06-26 13:51:11', '9472464/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(33, 18, '22', 'Transporte e distribuição de água potável destinado aos municípios de Arinos, Chapada Gaúcha, Riachinho e Urucuia.', 0, 9, '51508.80', 52.00, 'Metros Cúbicos', '2025-06-26 13:52:26', '2025-06-26 13:52:26', '9472471/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(34, 18, '23', 'Transporte e distribuição de água potável destinado aos municípios de Buenópolis, Claro dos Pocões, Francisco Dumont, Jequitaí e Joaquim Felício.', 0, 5, '40647.60', 50.88, 'Metros Cúbicos', '2025-06-26 13:53:58', '2025-06-26 13:53:58', '9472474/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(35, 18, '24', 'Transporte e distribuição de água potável destinado aos municípios de Bocaiúva, Engenheiro Navarro, Guaraciama e Olhos D\'água.', 0, 7, '44032.80', 34.81, 'Metros Cúbicos', '2025-06-26 13:55:13', '2025-06-26 13:55:13', '9472476/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(36, 18, '25', 'Transporte e distribuição de água potável, devidamente captado nos pontos indicados, destinado aos municípios de Janaúba, Jaíba, e Verdelândia.', 0, 5, '38791.20', 37.93, 'Metros Cúbicos', '2025-06-26 13:56:23', '2025-06-26 13:56:23', '9472480/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(37, 18, '26', 'Transporte e distribuição de água potável, devidamente captado nos pontos indicados, destinado aos municípios de Espinosa, Mamonas e Monte Azul.', 0, 7, '37321.20', 33.48, 'Metros Cúbicos', '2025-06-26 13:57:24', '2025-06-26 13:57:24', '9472483/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(38, 18, '27', 'Transporte e distribuição de água potável destinado aos municípios de Catuti, Gameleiras, Mato Verde, Rio Pardo de Minas e Santo Antônio do Retiro.', 0, 7, '55574.40', 31.59, 'Metros Cúbicos', '2025-06-26 13:58:44', '2025-06-26 13:58:44', '9472492/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(39, 18, '28', 'Transporte e distribuição de água potável destinado aos municípios de Januária, Bonito de Minas, Cônego Marinho e Pedras de Maria da Cruz.', 0, 5, '53684.40', 36.00, 'Metros Cúbicos', '2025-06-26 14:00:09', '2025-06-26 14:00:09', '9472495/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(40, 18, '29', 'Transporte e distribuição de água potável destinado aos municípios de Porteirinha, Nova Porteirinha, Pai Pedro, Riacho dos Machados, Serranópolis de Minas.', 0, 3, '53676.00', 35.59, 'Metros Cúbicos', '2025-06-26 14:01:40', '2025-06-26 14:01:40', '9472496/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(41, 18, '30', 'Transporte e distribuição de água potável destinado aos municípios de Itacarambi, Manga, Matias Cardoso e São João das Missões.', 0, 5, '107007.60', 38.00, 'Metros Cúbicos', '2025-06-26 14:03:04', '2025-06-26 14:03:04', '9472499/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(42, 18, '31', 'Transporte e distribuição de água potável destinado aos municípios de Juvenília, Miravânia e Montalvânia.', 0, 5, '17581.20', 55.00, 'Metros Cúbicos', '2025-06-26 14:04:03', '2025-06-26 14:04:03', '9472500/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(43, 18, '33', 'Transporte e distribuição de água potável destinado aos municípios de Botumirim, Cristália e Grão Mogol.', 0, 11, '28534.80', 30.00, 'Metros Cúbicos', '2025-06-26 14:06:26', '2025-06-26 14:06:26', '9472501/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(44, 18, '34', 'Transporte e distribuição de água potável destinado aos municípios de Montes Claros, Itacambira e Juramento.', 0, 7, '11205.60', 40.18, 'Metros Cúbicos', '2025-06-26 14:07:52', '2025-06-26 14:07:52', '9472503/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(45, 18, '36', 'Transporte e distribuição de água potável destinado aos municípios de Ibiracatu, Japonvar, Lontra, São João da Ponte e Varzelândia.', 0, 13, '49047.60', 37.00, 'Metros Cúbicos', '2025-06-26 14:09:38', '2025-06-26 14:09:38', '9472505/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(46, 18, '37', 'Transporte e distribuição de água potável destinado aos municípios de Buritizeiro, Lassance, Pirapora e Várzea da Palma.', 0, 3, '16405.20', 46.85, 'Metros Cúbicos', '2025-06-26 14:11:30', '2025-06-26 14:11:30', '9472506/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(47, 18, '39', 'Transporte e distribuição de água potável destinado aos municípios de Ubaí, Campo Azul, Icaraí de Minas, Pintópolis e São Romão.', 0, 14, '26502.00', 46.84, 'Metros Cúbicos', '2025-06-26 14:12:54', '2025-06-26 14:12:54', '9472508/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(48, 18, '41', 'Transporte e distribuição de água potável destinado ao município de São Francisco.', 0, 3, '68485.20', 46.84, 'Metros Cúbicos', '2025-06-26 14:14:11', '2025-06-26 14:14:11', '0');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(49, 18, '43', 'Transporte e distribuição de água potável destinado aos municípios de Taiobeiras, Berizal, Curral de Dentro e Santa Cruz de Salinas.', 0, 6, '26107.20', 28.91, 'Metros Cúbicos', '2025-06-26 14:15:39', '2025-06-26 14:15:39', '9472509/2025');
INSERT INTO `tdap_lotes` (`id`, `ata_id`, `numero`, `nome`, `municipio_id`, `prestador_id`, `qtd_agua`, `valor`, `unidade`, `created_at`, `updated_at`, `contrato`) VALUES
	(56, 19, '1', 'cronograma tdap para testes', 0, 16, '10.00', 5.00, 'Metros Cúbicos', '2025-09-29 19:31:57', '2025-09-29 19:31:57', NULL);
/*!40000 ALTER TABLE `tdap_lotes` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
