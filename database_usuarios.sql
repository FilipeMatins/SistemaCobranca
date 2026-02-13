-- ==================== TABELA DE USUÁRIOS ====================
-- Execute este SQL no phpMyAdmin para criar a tabela de usuários

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    ultimo_acesso DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar usuário administrador padrão
-- Senha: admin123 (você deve trocar depois!)
INSERT INTO usuarios (nome, email, senha) VALUES (
    'Administrador',
    'admin@sistema.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- Índice para melhorar performance de login
CREATE INDEX idx_usuarios_email ON usuarios(email);


