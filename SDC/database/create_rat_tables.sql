-- ========================
-- RAT Database Tables (Normalized Schema)
-- ========================

-- DADOS GERAIS (Main BO/Occurrence Data)
-- Linked to rats table via rat_id
CREATE TABLE IF NOT EXISTS rat_relato_dados_gerais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rat_id VARCHAR(36) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    numero_bos VARCHAR(255) NOT NULL UNIQUE,
    data_ocorrencia DATETIME NOT NULL,
    hora_ocorrencia TIME NULL,
    local_origem VARCHAR(255) NOT NULL,
    local_destino VARCHAR(255) NULL,
    km_percorrido DECIMAL(10, 2) NULL,
    natureza VARCHAR(255) NOT NULL,
    categoria VARCHAR(255) NOT NULL,
    orgao_responsavel VARCHAR(255) NOT NULL,
    descricao LONGTEXT NULL,
    status ENUM('rascunho', 'em_andamento', 'finalizado', 'cancelado') DEFAULT 'rascunho',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rat_id) REFERENCES rats(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_rat (rat_id),
    INDEX idx_usuario (usuario_id)
);

-- ENVOLVIDOS (Involved Persons - Victims, Aggressors, Witnesses)
CREATE TABLE IF NOT EXISTS rat_relato_envolvidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rat_id VARCHAR(36) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NULL UNIQUE,
    data_nascimento DATE NULL,
    escolaridade VARCHAR(255) NULL,
    profissao VARCHAR(255) NULL,
    sexo ENUM('M', 'F') NULL,
    estado_civil VARCHAR(255) NULL,
    rg VARCHAR(20) NULL,
    fone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    cnaes VARCHAR(255) NULL,
    endereco VARCHAR(255) NOT NULL,
    numero VARCHAR(10) NULL,
    complemento VARCHAR(255) NULL,
    bairro VARCHAR(255) NOT NULL,
    cidade VARCHAR(255) NOT NULL,
    uf CHAR(2) NOT NULL,
    cep VARCHAR(10) NULL,
    telefone_comercial VARCHAR(20) NULL,
    telefone_referencia VARCHAR(20) NULL,
    tipo_envolvimento VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rat_id) REFERENCES rats(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_rat_tipo (rat_id, tipo_envolvimento),
    INDEX idx_cpf (cpf)
);

-- RECURSOS (Employed Resources - Vehicles, Personnel, Materials)
CREATE TABLE IF NOT EXISTS rat_relato_recursos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rat_id VARCHAR(36) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo_recurso VARCHAR(255) NOT NULL,
    categoria VARCHAR(255) NOT NULL,
    orgao_responsavel VARCHAR(255) NOT NULL,
    identificacao VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    local_origem VARCHAR(255) NOT NULL,
    local_destino VARCHAR(255) NULL,
    data_hora_saida DATETIME NULL,
    data_hora_chegada DATETIME NULL,
    km_percorrido DECIMAL(10, 2) NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rat_id) REFERENCES rats(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_rat_tipo (rat_id, tipo_recurso)
);

-- VISTORIAS (Technical Inspection Reports)
CREATE TABLE IF NOT EXISTS rat_relato_vistorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rat_id VARCHAR(36) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    data_vistoria DATE NOT NULL,
    local_vistoria VARCHAR(255) NOT NULL,
    descricao_danos TEXT NULL,
    parecer TEXT NOT NULL,
    recomendacoes TEXT NULL,
    profissional VARCHAR(255) NOT NULL,
    cargo VARCHAR(255) NOT NULL,
    orgao VARCHAR(255) NOT NULL,
    numero_register VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rat_id) REFERENCES rats(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_rat (rat_id)
);

-- HISTÓRICO (Audit Trail - History of Actions)
CREATE TABLE IF NOT EXISTS rat_ocorrencia_historicos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rat_id VARCHAR(36) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(255) NOT NULL,
    detalhes TEXT NULL,
    entidade VARCHAR(255) NOT NULL,
    entidade_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rat_id) REFERENCES rats(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_rat_data (rat_id, created_at)
);

-- Show result
SHOW TABLES LIKE 'rat_%';
