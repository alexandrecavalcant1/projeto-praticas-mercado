-- ==============================================================================
-- PROJETO APLICADO: PRÁTICAS DE MERCADO - SECURE BY DESIGN
-- Avaliação Final - Pós-Graduação em Segurança da Informação & Análise Forense
-- UNCISAL (Universidade Estadual de Ciências da Saúde de Alagoas)
-- ==============================================================================

-- 1. Criação do Banco de Dados com Charset UTF-8 Seguro
CREATE DATABASE IF NOT EXISTS `sistema_seguranca` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sistema_seguranca`;

-- 2. Tabela de Usuários com Atributos de Segurança e Controle de Quarentena
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `tentativas_falhas` INT DEFAULT 0,
    `bloqueado_ate` DATETIME NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Inserção do Usuário Administrador de Testes
-- Credenciais de Avaliação:
--   E-mail: admin@uncisal.edu.br
--   Senha:  Admin@123456
-- Hash gerado via password_hash('Admin@123456', PASSWORD_DEFAULT):
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tentativas_falhas`, `bloqueado_ate`)
VALUES (
    'Administrador de Segurança',
    'admin@uncisal.edu.br',
    '$2y$10$wT8v7V7zF2R6A3a8j2KquO0sZ7Q6I2d8y0iG8ZkC9mU1l1o1N7v9m',
    0,
    NULL
);
