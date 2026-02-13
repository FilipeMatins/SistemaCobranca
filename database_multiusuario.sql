-- ==================== ATUALIZAÇÃO PARA MULTI-USUÁRIO ====================
-- Execute este SQL no phpMyAdmin para adicionar suporte a múltiplos usuários
-- IMPORTANTE: Faça backup do banco antes de executar!

-- 1. Adicionar coluna usuario_id na tabela notinhas
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER id;

-- 2. Adicionar coluna usuario_id na tabela clientes
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER id;

-- 3. Adicionar coluna usuario_id na tabela configuracoes
ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER id;

-- 4. Adicionar coluna usuario_id na tabela empresas
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER id;

-- 5. Vincular dados existentes ao usuário admin (id=1)
UPDATE notinhas SET usuario_id = 1 WHERE usuario_id IS NULL;
UPDATE clientes SET usuario_id = 1 WHERE usuario_id IS NULL;
UPDATE configuracoes SET usuario_id = 1 WHERE usuario_id IS NULL;
UPDATE empresas SET usuario_id = 1 WHERE usuario_id IS NULL;

-- 6. Criar índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_notinhas_usuario ON notinhas(usuario_id);
CREATE INDEX IF NOT EXISTS idx_clientes_usuario ON clientes(usuario_id);
CREATE INDEX IF NOT EXISTS idx_configuracoes_usuario ON configuracoes(usuario_id);
CREATE INDEX IF NOT EXISTS idx_empresas_usuario ON empresas(usuario_id);

-- 7. Adicionar tabela de tokens para recuperação de senha
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_token (token),
    INDEX idx_email (email)
);

-- 8. Adicionar campos extras na tabela usuarios
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) NULL AFTER email;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) DEFAULT 0 AFTER telefone;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS plano VARCHAR(20) DEFAULT 'free' AFTER email_verificado;

-- Planos disponíveis: free, basic, premium, enterprise


