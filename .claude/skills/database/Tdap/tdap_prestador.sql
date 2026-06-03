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

-- Copiando estrutura para tabela dbsdc.tdap_prestador
CREATE TABLE IF NOT EXISTS `tdap_prestador` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(110) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnpj` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `representante` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(110) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel1` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel2` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(110) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela dbsdc.tdap_prestador: 13 rows
/*!40000 ALTER TABLE `tdap_prestador` DISABLE KEYS */;
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(1, 'JONAS ELIAS DE FREITAS TRANSPORTES', '34178669000190', 'JONAS ELIAS DE FREITAS', 'transportesjonaselias@gmail.com', '15478064602', '(38)99955-945', '(38)38438-151', 'RUA CINCO, N.º 128', 'CAMPO VERDE', 'NOVORIZONTE', 'MG', '39568-000', '2025-06-24 16:18:40', '2025-09-08 17:20:34');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(2, 'DOUGLAS SANTOS TRANSPORTES E SERVIÇOS LTDA', '46641669000163', 'DOUGLAS DOS SANTOS', 'douglas.s.santos@hotmail.com', '07892449698', '(38)99951-600', NULL, 'RUA CINCO, N.º 175', 'CAMPO VERDE', 'NOVORIZONTE', 'MG', '39568-000', '2025-06-24 17:16:43', '2025-06-24 18:31:29');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(3, 'WHESLLEY XAVIER ALVES SILVA 17581952665', '41556695000170', 'WHESLLEY XAVIER ALVES SILVA', 'silvaneidex@hotmail.com', '07428719674', '(38)99104-420', NULL, 'RUA HONORATO N FERREIRA, N.º 133', 'CENTRO', 'MAMONAS', 'MG', '39516-000', '2025-06-24 17:23:42', '2025-06-24 18:31:42');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(9, 'BRASÃO INDUSTRIA FLORESTAL LTDA', '20967369000185', 'DERACI JOSÉ DE OLIVEIRA', 'solusreflorestamento@hotmail.com', '88069257687', '(38)34899-88_', '(38)38321-894', 'RODOVIA MGC 122, S/N, KM 72', 'ZONA RURAL', 'MATO VERDE', 'MG', '39527-000', '2025-06-24 18:28:25', '2025-06-24 18:33:12');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(5, 'LOCANORTHI SERVIÇOS LTDA.', '50782928000143', 'THIAGO RODRIGUES DE OLIVEIRA', 'locanorthi@gmail.com', '', '(21)96723-153', NULL, 'RODOVIA MGC 122, S/N, KM 72', 'ZONA RURAL', 'MATO VERDE', 'MG', '39527-000', '2025-06-24 17:40:37', '2025-06-24 18:32:16');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(6, 'LUIZ PEREIRA 08784935812 - ME', '25170449000129', 'LUIZ PEREIRA', 'latransportesminas@gmail.com', '08784935812', '(38)99914-563', NULL, 'RUA CAIÇARA, N.º 50', 'SÃO VICENTE', 'INDAIABIRA', 'MG', '39536-000', '2025-06-24 17:49:32', '2025-06-24 18:32:39');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(7, 'SANTA FÉ PRESTAÇÃO DE SERVIÇOS LTDA', '37628085000167', 'RONDINELY EVANGELISTA BARROSO', 'santafetransporte15@gmail.com', '05922902636', '(38)99918-527', NULL, 'RUA BOCAIÚVA, N.º 49', 'CENTRO', 'GUARACIAMA', 'MG', '39397-000', '2025-06-24 17:56:21', '2025-09-09 20:31:00');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(11, 'ANDRÉ FILOGONIO PEREIRA ME', '05687624000142', 'ANDRÉ FILOGONIO PEREIRA', 'andrefilo2@yahoo.com.br', '04582704662', '(38)99999-913', NULL, 'RUA EUMENEGIDO ALVES, N.º 04', 'CENTRO', 'GUARACIAMA', 'MG', '39397-000', '2025-06-24 18:44:41', '2025-06-24 18:44:41');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(8, 'LEGUS TRANSPORTES E SERVIÇOS LTDA', '33562113000130', 'ROGÉRIO MENDES AMORIM', 'ronelegu@gmail.com', '07326829675', '(38)99943-612', NULL, 'RUA RIO PARDO, N.º 120', 'BOA VISTA', 'INDAIABIRA', 'MG', '39536-000', '2025-06-24 18:10:13', '2025-06-24 18:33:55');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(10, 'DONNER SERVIÇOS LTDA', '10502423000163', 'JOSÉ JAILSON DE OLIVEIRA SAMPAIO', 'contato@donnerservicos.com.br', '04954609313', '(88)98215-227', NULL, 'RUA GENERAL SAMPAIO, N.º 1044, SALA 6, CENTRO, NOVA RUSSAS/CE', 'CENTRO', 'NOVA RUSSAS', 'CE', '62200-000', '2025-06-24 18:29:52', '2025-06-25 18:01:08');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(13, 'WESLEY BARBOSA SILVEIRA LTDA', '54311189000162', 'WESLEY BARBOSA SILVEIRA', 'wesleybarbosasilveira72@gmail.com', '80283918691', '(38)99982-507', NULL, 'RUA JOSÉ FERREIRA, N.º 135, CENTRO, CATUTI/MG', 'CENTRO', 'CATUTI', 'MG', '39526-000', '2025-06-25 11:35:44', '2025-06-25 11:35:44');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(14, 'GENILCE L. ROCHA TRANSPORTES', '09294873000129', 'GENILCE LIMA ROCHA', 'genilson829@gmail.com', '00668004630', '(38)99948-898', NULL, 'RUA JOAQUIM ANTÔNIO RIBEIRO, N.º 43, CENTRO, S. JOÃO DO PARAISO/MG', 'CENTRO', 'SÃO JOÃO DO PARAISO', 'MG', '39540-000', '2025-06-25 17:52:42', '2025-06-25 17:54:56');
INSERT INTO `tdap_prestador` (`id`, `nome`, `cnpj`, `representante`, `email`, `cpf`, `tel1`, `tel2`, `endereco`, `bairro`, `cidade`, `uf`, `cep`, `created_at`, `updated_at`) VALUES
	(16, 'TRANSPORTADORA DO TIÃO', '12121212121212', 'TIÃO DA SILVA', 'tiao@gmail.com', '12121212121', '(31)11111-111', '(31)22222-222', 'RUA DOS TOINHO,44', 'ILHA CITY', 'BELO HORIZONTE', 'MG', '31111-111', '2025-09-29 19:30:44', '2025-09-29 19:30:44');
/*!40000 ALTER TABLE `tdap_prestador` ENABLE KEYS */;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
