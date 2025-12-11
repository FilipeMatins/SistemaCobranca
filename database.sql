-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS sistema_cobranca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_cobranca;

-- Tabela de Empresas (onde vendeu)
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Clientes Cadastrados (para autocomplete)
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Configurações
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Configurações padrão
INSERT INTO configuracoes (chave, valor) VALUES 
('chave_pix', '67991233362'),
('nome_vendedor', 'Filipe que vende requeijão e doces'),
('mensagem_cobranca', 'Bom dia {nome} tudo bem? {vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {valor} Chave pix {pix}');

-- Tabela de Notinhas
CREATE TABLE notinhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    data_cobranca DATE NOT NULL,
    enviada TINYINT(1) DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Tabela de Clientes da Notinha
CREATE TABLE notinha_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notinha_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    msg_enviada TINYINT(1) DEFAULT 0,
    data_envio DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notinha_id) REFERENCES notinhas(id) ON DELETE CASCADE
);

-- Índice para buscar excluídos
CREATE INDEX idx_notinhas_deleted ON notinhas(deleted_at);
