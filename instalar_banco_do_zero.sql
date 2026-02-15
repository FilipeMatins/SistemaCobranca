-- ==================== INSTALAÇÃO DO BANCO DO ZERO ====================
-- Use este arquivo quando for excluir o banco e criar tudo de novo.
-- Execute no phpMyAdmin ou: mysql -u usuario -p < instalar_banco_do_zero.sql

DROP DATABASE IF EXISTS sistema_cobranca;
CREATE DATABASE sistema_cobranca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_cobranca;

-- ==================== USUÁRIOS ====================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20) NULL,
    email_verificado TINYINT(1) DEFAULT 0,
    plano VARCHAR(20) DEFAULT 'free',
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX idx_usuarios_email ON usuarios(email);

-- Admin padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha) VALUES (
    'Administrador',
    'admin@sistema.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- ==================== RECUPERAÇÃO DE SENHA ====================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_token (token),
    INDEX idx_email (email)
);

-- ==================== EMPRESAS ====================
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nome VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_empresa_usuario (usuario_id, nome)
);
CREATE INDEX idx_empresas_usuario ON empresas(usuario_id);

-- ==================== CLIENTES ====================
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nome VARCHAR(255) NOT NULL,
    telefone VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_clientes_usuario ON clientes(usuario_id);
CREATE INDEX idx_clientes_nome ON clientes(nome);

-- ==================== CONFIGURAÇÕES ====================
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX idx_configuracoes_usuario ON configuracoes(usuario_id);

-- Config padrão do admin (id=1)
INSERT INTO configuracoes (usuario_id, chave, valor) VALUES
(1, 'chave_pix', ''),
(1, 'nome_vendedor', ''),
(1, 'mensagem_padrao', 'Olá {nome}! Aqui é {vendedor}, passando para lembrar do pagamento de {valor}. Chave PIX: {pix}');

-- ==================== NOTINHAS ====================
CREATE TABLE notinhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    empresa_id INT NOT NULL,
    data_cobranca DATE NOT NULL,
    enviada TINYINT(1) DEFAULT 0,
    deleted_at DATETIME NULL,
    inadimplente_at DATETIME NULL,
    recebido_at DATETIME NULL,
    observacoes TEXT NULL,
    numero_parcela INT DEFAULT 1,
    total_parcelas INT DEFAULT 1,
    parcela_origem_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (parcela_origem_id) REFERENCES notinhas(id) ON DELETE SET NULL
);
CREATE INDEX idx_notinhas_usuario ON notinhas(usuario_id);
CREATE INDEX idx_notinhas_deleted ON notinhas(deleted_at);
CREATE INDEX idx_notinhas_inadimplente ON notinhas(inadimplente_at);
CREATE INDEX idx_notinhas_recebido ON notinhas(recebido_at);
CREATE INDEX idx_notinhas_data ON notinhas(data_cobranca);

-- ==================== CLIENTES DA NOTINHA ====================
CREATE TABLE notinha_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notinha_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    telefone VARCHAR(50) NULL,
    msg_enviada TINYINT(1) DEFAULT 0,
    data_envio DATETIME NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notinha_id) REFERENCES notinhas(id) ON DELETE CASCADE
);
CREATE INDEX idx_notinha_clientes_deleted ON notinha_clientes(deleted_at);

-- ==================== RECEBIMENTOS ====================
CREATE TABLE notinha_recebimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notinha_id INT NOT NULL,
    cliente_id INT NULL,
    usuario_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    recebido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacao TEXT NULL,
    CONSTRAINT fk_recebimentos_notinha FOREIGN KEY (notinha_id) REFERENCES notinhas(id),
    CONSTRAINT fk_recebimentos_cliente FOREIGN KEY (cliente_id) REFERENCES notinha_clientes(id)
);
CREATE INDEX idx_recebimentos_notinha ON notinha_recebimentos(notinha_id);
CREATE INDEX idx_recebimentos_usuario ON notinha_recebimentos(usuario_id);
CREATE INDEX idx_recebimentos_data ON notinha_recebimentos(recebido_em);

-- Fim
SELECT 'Banco instalado do zero com sucesso!' AS status;
