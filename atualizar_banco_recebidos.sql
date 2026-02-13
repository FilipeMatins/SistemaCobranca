-- Adiciona coluna para marcar notinhas como recebidas
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS recebido_at DATETIME NULL;

-- Índice para melhorar performance de consultas por recebidos
CREATE INDEX IF NOT EXISTS idx_notinhas_recebido ON notinhas(recebido_at);

-- Campo de observações na notinha
ALTER TABLE notinhas ADD COLUMN IF NOT EXISTS observacoes TEXT NULL;

-- Tabela para registrar recebimentos (inclusive parciais)
CREATE TABLE IF NOT EXISTS notinha_recebimentos (
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

CREATE INDEX IF NOT EXISTS idx_recebimentos_notinha ON notinha_recebimentos(notinha_id);
CREATE INDEX IF NOT EXISTS idx_recebimentos_usuario ON notinha_recebimentos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_recebimentos_data ON notinha_recebimentos(recebido_em);

-- Popular tabela de recebimentos com dados antigos (notinhas já marcadas como recebidas)
INSERT INTO notinha_recebimentos (notinha_id, cliente_id, usuario_id, valor, recebido_em)
SELECT 
    n.id as notinha_id,
    NULL as cliente_id,
    n.usuario_id,
    SUM(nc.valor) as valor,
    n.recebido_at as recebido_em
FROM notinhas n
JOIN notinha_clientes nc ON nc.notinha_id = n.id
LEFT JOIN notinha_recebimentos nr ON nr.notinha_id = n.id
WHERE n.recebido_at IS NOT NULL
  AND nr.id IS NULL
GROUP BY n.id, n.usuario_id, n.recebido_at;


