#!/usr/bin/env php
<?php

/**
 * Setup Script para RAT Unified Backend
 * Execute em: docker exec newsdc_db
 */

// Dados de conexão
$host = 'localhost';
$user = 'sdc';
$password = 'secret';
$database = 'sdc';

try {
    // Conexão com MySQL
    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    echo "✓ Connected to MySQL\n";

    // Criar tabelas
    $tables = [
        // Dados Gerais
        "CREATE TABLE IF NOT EXISTS rat_relato_dados_gerais (
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
            INDEX(ocorrencia_id),
            INDEX(usuario_id),
            FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
        )",

        // Envolvidos
        "CREATE TABLE IF NOT EXISTS rat_relato_envolvidos (
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
            INDEX(ocorrencia_id),
            INDEX(usuario_id),
            INDEX(tipo_envolvimento),
            FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
        )",

        // Recursos
        "CREATE TABLE IF NOT EXISTS rat_relato_recursos (
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
            INDEX(ocorrencia_id),
            INDEX(usuario_id),
            INDEX(tipo_recurso),
            FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
        )",

        // Vistoria
        "CREATE TABLE IF NOT EXISTS rat_relato_vistorias (
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
            FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
        )",

        // Histórico
        "CREATE TABLE IF NOT EXISTS rat_ocorrencia_historicos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ocorrencia_id BIGINT UNSIGNED NOT NULL,
            usuario_id BIGINT UNSIGNED NOT NULL,
            acao VARCHAR(100),
            detalhes TEXT,
            entidade VARCHAR(100),
            entidade_id BIGINT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(ocorrencia_id),
            INDEX(created_at),
            FOREIGN KEY(usuario_id) REFERENCES users(id) ON DELETE RESTRICT
        )",
    ];

    foreach ($tables as $sql) {
        if ($conn->query($sql)) {
            echo "✓ Table created/verified\n";
        } else {
            echo "✗ Error: " . $conn->error . "\n";
        }
    }

    $conn->close();
    echo "\n✅ Database setup completed successfully!\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
