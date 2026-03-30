-- RAT Unified Backend - Database Setup Script
-- Execute via: docker exec newsdc_db mysql -u sdc -psecret sdc < setup-rat-db.sql

-- ============================================================================
-- TABELAS RAT RELATOS
-- ============================================================================

-- Dados Gerais
CREATE TABLE IF NOT EXISTS rat_relato_dados_gerais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id BIGINT UNSIGNED,
    usuario_id BIGINT UNSIGNED NOT NULL,
    numero_bos VARCHAR(255) UNIQUE NOT NULL,
    data_ocorrencia DATETIME NOT NULL,
    hora_ocorrencia TIME,
    local_origem VARCHAR(255) NOT NULL,
    local_destino VARCHAR(255),
    km_percorrido DECIMAL(10, 2),
    natureza VARCHAR(255),
    categoria VARCHAR(255),
    orgao_responsavel VARCHAR(255),
    descricao LONGTEXT,
    status ENUM('rascunho', 'em_andamento', 'finalizado', 'cancelado') DEFAULT 'rascunho',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia(ocorrencia_id),
    INDEX idx_usuario(usuario_id),
    FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Envolvidos
CREATE TABLE IF NOT EXISTS rat_relato_envolvidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(20) UNIQUE,
    data_nascimento DATE,
    escolaridade VARCHAR(100),
    profissao VARCHAR(100),
    sexo ENUM('M', 'F'),
    estado_civil VARCHAR(50),
    rg VARCHAR(50),
    fone VARCHAR(20),
    email VARCHAR(100),
    cnaes VARCHAR(255),
    endereco VARCHAR(255) NOT NULL,
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf VARCHAR(2),
    cep VARCHAR(10),
    telefone_comercial VARCHAR(20),
    telefone_referencia VARCHAR(20),
    tipo_envolvimento VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia(ocorrencia_id),
    INDEX idx_usuario(usuario_id),
    INDEX idx_tipo_envolvimento(tipo_envolvimento),
    FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recursos
CREATE TABLE IF NOT EXISTS rat_relato_recursos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo_recurso VARCHAR(100),
    categoria VARCHAR(100),
    orgao_responsavel VARCHAR(100),
    identificacao VARCHAR(100),
    descricao TEXT,
    local_origem VARCHAR(255),
    local_destino VARCHAR(255),
    data_hora_saida DATETIME,
    data_hora_chegada DATETIME,
    km_percorrido DECIMAL(10, 2),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia(ocorrencia_id),
    INDEX idx_usuario(usuario_id),
    INDEX idx_tipo_recurso(tipo_recurso),
    FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vistoria
CREATE TABLE IF NOT EXISTS rat_relato_vistorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    data_vistoria DATE,
    local_vistoria VARCHAR(255),
    descricao_danos TEXT,
    parecer TEXT,
    recomendacoes TEXT,
    profissional VARCHAR(255),
    cargo VARCHAR(100),
    orgao VARCHAR(100),
    numero_register VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia(ocorrencia_id),
    INDEX idx_usuario(usuario_id),
    FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico
CREATE TABLE IF NOT EXISTS rat_ocorrencia_historicos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ocorrencia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(100),
    detalhes TEXT,
    entidade VARCHAR(100),
    entidade_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ocorrencia(ocorrencia_id),
    INDEX idx_created(created_at),
    FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Confirmação
SELECT 'RAT Database Setup Completed Successfully!' AS status;
