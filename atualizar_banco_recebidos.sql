-- Adiciona coluna para marcar notinhas como recebidas
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS recebido_at DATETIME NULL;

-- Índice para melhorar performance de consultas por recebidos
CREATE INDEX IF NOT EXISTS idx_notinhas_recebido ON notinhas(recebido_at);

-- Campo de observações na notinha
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS observacoes TEXT NULL;

