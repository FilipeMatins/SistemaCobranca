-- Script para atualizar o banco de dados existente
-- Execute este script se você já tem dados no banco

USE sistema_cobranca;

-- Adicionar colunas de inadimplência (se não existir)
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS inadimplente_at DATETIME NULL;

-- Adicionar colunas de parcelamento
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS numero_parcela INT DEFAULT 1;
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS total_parcelas INT DEFAULT 1;
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS parcela_origem_id INT NULL;

-- Adicionar coluna deleted_at em notinha_clientes (se não existir)
ALTER TABLE notinha_clientes ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

-- Criar índices (ignorar erro se já existirem)
CREATE INDEX IF NOT EXISTS idx_notinhas_inadimplente ON notinhas(inadimplente_at);
CREATE INDEX IF NOT EXISTS idx_notinhas_data ON notinhas(data_cobranca);
CREATE INDEX IF NOT EXISTS idx_clientes_deleted ON notinha_clientes(deleted_at);

-- Mensagem de sucesso
SELECT 'Banco de dados atualizado com sucesso!' as status;


