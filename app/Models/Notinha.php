<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Notinha
{
    private PDO $pdo;
    private ?int $usuarioId = null;
    
    public function __construct(?int $usuarioId = null)
    {
        $this->pdo = Database::getInstance();
        $this->usuarioId = $usuarioId;
    }
    
    public function setUsuarioId(int $usuarioId): void
    {
        $this->usuarioId = $usuarioId;
    }

    /** Evita exibir email no campo telefone (dados antigos gravados por engano). */
    private static function sanitizarTelefone(?string $telefone): string
    {
        if ($telefone === null || $telefone === '') {
            return '';
        }
        return (strpos($telefone, '@') !== false) ? '' : $telefone;
    }

    public function limparExcluidosAntigos(): void
    {
        // Limpa notinhas excluídas há mais de 15 dias
        $this->pdo->exec("DELETE FROM notinhas WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 15 DAY)");
        // Limpa clientes excluídos há mais de 15 dias
        $this->pdo->exec("DELETE FROM notinha_clientes WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 15 DAY)");
    }
    
    public function listarAtivas(): array
    {
        $sql = "
            SELECT 
                n.id,
                n.data_cobranca,
                n.enviada,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome,
                COALESCE(SUM(nr.valor), 0) as total_recebido
            FROM notinhas n
            JOIN empresas e ON n.empresa_id = e.id
            LEFT JOIN notinha_recebimentos nr ON nr.notinha_id = n.id
            WHERE n.deleted_at IS NULL 
            AND n.inadimplente_at IS NULL 
            AND n.recebido_at IS NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND n.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql . " GROUP BY n.id, n.data_cobranca, n.enviada, n.created_at, e.id, e.nome ORDER BY n.data_cobranca DESC, n.created_at DESC");
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql . " GROUP BY n.id, n.data_cobranca, n.enviada, n.created_at, e.id, e.nome ORDER BY n.data_cobranca DESC, n.created_at DESC");
        }
        
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientes($notinha['id']);
            $notinha['total_original'] = $this->obterTotalNotinha($notinha['id']);
        }
        
        return $notinhas;
    }
    
    public function listarInadimplentes(): array
    {
        $sql = "
            SELECT 
                n.id,
                n.data_cobranca,
                n.inadimplente_at,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome
            FROM notinhas n
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.inadimplente_at IS NOT NULL AND n.deleted_at IS NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND n.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql . " ORDER BY n.inadimplente_at DESC");
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql . " ORDER BY n.inadimplente_at DESC");
        }
        
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientes($notinha['id']);
        }
        
        return $notinhas;
    }
    
    public function listarExcluidas(): array
    {
        $sql = "
            SELECT 
                n.id,
                n.data_cobranca,
                n.deleted_at,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome,
                DATEDIFF(DATE_ADD(n.deleted_at, INTERVAL 15 DAY), NOW()) as dias_restantes
            FROM notinhas n
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.deleted_at IS NOT NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND n.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql . " ORDER BY n.deleted_at DESC");
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql . " ORDER BY n.deleted_at DESC");
        }
        
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientesSemEnvio($notinha['id']);
        }
        
        return $notinhas;
    }
    
    private function buscarClientes(int $notinhaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, valor, telefone, msg_enviada, data_envio
            FROM notinha_clientes
            WHERE notinha_id = ? AND deleted_at IS NULL
            ORDER BY id
        ");
        $stmt->execute([$notinhaId]);
        $lista = $stmt->fetchAll();
        foreach ($lista as &$row) {
            $row['telefone'] = self::sanitizarTelefone($row['telefone'] ?? null);
        }
        return $lista;
    }
    
    public function buscarClientesExcluidos(int $notinhaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, valor, telefone, deleted_at
            FROM notinha_clientes
            WHERE notinha_id = ? AND deleted_at IS NOT NULL
            ORDER BY deleted_at DESC
        ");
        $stmt->execute([$notinhaId]);
        $lista = $stmt->fetchAll();
        foreach ($lista as &$row) {
            $row['telefone'] = self::sanitizarTelefone($row['telefone'] ?? null);
        }
        return $lista;
    }
    
    public function listarTodosClientesExcluidos(): array
    {
        $sql = "
            SELECT 
                nc.id,
                nc.nome,
                nc.valor,
                nc.telefone,
                nc.deleted_at,
                n.id as notinha_id,
                n.data_cobranca,
                e.nome as empresa_nome,
                DATEDIFF(DATE_ADD(nc.deleted_at, INTERVAL 15 DAY), NOW()) as dias_restantes
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            JOIN empresas e ON n.empresa_id = e.id
            WHERE nc.deleted_at IS NOT NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND n.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql . " ORDER BY nc.deleted_at DESC");
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql . " ORDER BY nc.deleted_at DESC");
        }
        
        $lista = $stmt->fetchAll();
        foreach ($lista as &$row) {
            $row['telefone'] = self::sanitizarTelefone($row['telefone'] ?? null);
        }
        return $lista;
    }
    
    public function excluirClientePermanente(int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM notinha_clientes WHERE id = ?");
        $stmt->execute([$clienteId]);
        return $stmt->rowCount() > 0;
    }
    
    public function excluirCliente(int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinha_clientes SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$clienteId]);
        return $stmt->rowCount() > 0;
    }
    
    public function restaurarCliente(int $clienteId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinha_clientes SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$clienteId]);
        return $stmt->rowCount() > 0;
    }
    
    public function buscarNotinhaIdDoCliente(int $clienteId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT notinha_id FROM notinha_clientes WHERE id = ?");
        $stmt->execute([$clienteId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['notinha_id'] : null;
    }
    
    private function buscarClientesSemEnvio(int $notinhaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nome, valor, telefone
            FROM notinha_clientes
            WHERE notinha_id = ?
        ");
        $stmt->execute([$notinhaId]);
        $lista = $stmt->fetchAll();
        foreach ($lista as &$row) {
            $row['telefone'] = self::sanitizarTelefone($row['telefone'] ?? null);
        }
        return $lista;
    }
    
    public function criar(int $empresaId, string $dataCobranca, int $numeroParcela = 1, int $totalParcelas = 1, ?int $parcelaOrigem = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO notinhas (empresa_id, data_cobranca, numero_parcela, total_parcelas, parcela_origem_id, usuario_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$empresaId, $dataCobranca, $numeroParcela, $totalParcelas, $parcelaOrigem, $this->usuarioId]);
        return (int) $this->pdo->lastInsertId();
    }
    
    public function criarComParcelas(int $empresaId, string $dataInicial, array $clientes, int $numParcelas): array
    {
        $notinhasIds = [];
        $parcelaOrigemId = null;
        
        // Calcula valor por parcela para cada cliente
        $clientesPorParcela = array_map(function($c) use ($numParcelas) {
            $valorTotal = floatval(str_replace([',', 'R$', ' '], ['.', '', ''], $c['valor']));
            return [
                'nome' => $c['nome'],
                'valor' => $valorTotal / $numParcelas,
                'telefone' => self::sanitizarTelefone($c['telefone'] ?? null)
            ];
        }, $clientes);
        
        for ($i = 1; $i <= $numParcelas; $i++) {
            // Calcula a data de cada parcela (mensal)
            $dataCobranca = date('Y-m-d', strtotime($dataInicial . ' + ' . ($i - 1) . ' months'));
            
            // Cria a notinha
            $notinhaId = $this->criar($empresaId, $dataCobranca, $i, $numParcelas, $parcelaOrigemId);
            
            // Guarda a primeira como origem
            if ($i === 1) {
                $parcelaOrigemId = $notinhaId;
            }
            
            // Adiciona os clientes
            foreach ($clientesPorParcela as $cliente) {
                $this->adicionarCliente($notinhaId, $cliente['nome'], $cliente['valor'], $cliente['telefone']);
            }
            
            $notinhasIds[] = $notinhaId;
        }
        
        return $notinhasIds;
    }
    
    public function atualizar(int $id, int $empresaId, string $dataCobranca): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinhas SET empresa_id = ?, data_cobranca = ? WHERE id = ?");
        return $stmt->execute([$empresaId, $dataCobranca, $id]);
    }
    
    public function adicionarCliente(int $notinhaId, string $nome, float $valor, string $telefone): void
    {
        $telefone = self::sanitizarTelefone($telefone);
        $stmt = $this->pdo->prepare("
            INSERT INTO notinha_clientes (notinha_id, nome, valor, telefone) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$notinhaId, $nome, $valor, $telefone]);
    }
    
    public function removerClientes(int $notinhaId): void
    {
        // Remove apenas clientes ativos (não exclui os que já estão na lixeira)
        $stmt = $this->pdo->prepare("DELETE FROM notinha_clientes WHERE notinha_id = ? AND deleted_at IS NULL");
        $stmt->execute([$notinhaId]);
    }
    
    public function marcarClientesEnviados(array $clienteIds): void
    {
        if (empty($clienteIds)) return;
        
        $placeholders = str_repeat('?,', count($clienteIds) - 1) . '?';
        $stmt = $this->pdo->prepare("UPDATE notinha_clientes SET msg_enviada = 1, data_envio = NOW() WHERE id IN ($placeholders)");
        $stmt->execute($clienteIds);
    }
    
    public function moverParaLixeira(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinhas SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    
    public function moverParaInadimplentes(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinhas SET inadimplente_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    
    public function marcarComoRecebido(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinhas SET recebido_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    
    public function marcarClienteRecebido(int $clienteId): bool
    {
        // Marca o cliente como recebido usando deleted_at com um padrão especial
        // Usamos a mesma coluna mas identificamos pelo contexto
        $stmt = $this->pdo->prepare("UPDATE notinha_clientes SET deleted_at = NOW(), msg_enviada = 2 WHERE id = ?");
        $stmt->execute([$clienteId]);
        return $stmt->rowCount() > 0;
    }
    
    public function contarClientesAtivos(int $notinhaId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total 
            FROM notinha_clientes 
            WHERE notinha_id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$notinhaId]);
        $result = $stmt->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Registra um recebimento (parcial ou total) para uma notinha.
     * Também verifica se o total recebido já cobre o valor total e,
     * em caso afirmativo, marca a notinha como totalmente recebida.
     */
    public function registrarRecebimentoParcial(int $notinhaId, float $valor, ?int $clienteId = null): void
    {
        if ($valor <= 0) {
            return;
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO notinha_recebimentos 
            SET notinha_id = ?, 
                cliente_id = ?, 
                usuario_id = ?, 
                valor = ?, 
                recebido_em = NOW()
        ");
        $stmt->execute([$notinhaId, $clienteId, $this->usuarioId, $valor]);
        
        // Se recebeu de um cliente específico (pela edição), marca esse cliente como recebido (sai da lista)
        if ($clienteId !== null) {
            $this->marcarClienteRecebido($clienteId);
        }
        
        // Verifica se já recebeu tudo desta notinha
        $totalNotinha = $this->obterTotalNotinha($notinhaId);
        $totalRecebido = $this->obterTotalRecebidoNotinha($notinhaId);
        
        if ($totalRecebido >= $totalNotinha && $totalNotinha > 0) {
            // Marca como totalmente recebida (mantém compatibilidade com lógica existente)
            $this->marcarComoRecebido($notinhaId);
        }
    }
    
    /**
     * Retorna o valor total (somando clientes) de uma notinha.
     */
    public function obterTotalNotinha(int $notinhaId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total
            FROM notinha_clientes
            WHERE notinha_id = ?
        ");
        $stmt->execute([$notinhaId]);
        $result = $stmt->fetch();
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Retorna o total já recebido (somando parciais) de uma notinha.
     */
    public function obterTotalRecebidoNotinha(int $notinhaId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total
            FROM notinha_recebimentos
            WHERE notinha_id = ?
        ");
        $stmt->execute([$notinhaId]);
        $result = $stmt->fetch();
        return (float) ($result['total'] ?? 0);
    }
    
    public function listarRecebidos(): array
    {
        $sql = "
            SELECT 
                n.id,
                n.data_cobranca,
                MAX(nr.recebido_em) as recebido_at,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome,
                COALESCE(SUM(nr.valor), 0) as total_recebido
            FROM notinha_recebimentos nr
            JOIN notinhas n ON nr.notinha_id = n.id
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.deleted_at IS NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND nr.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql . " GROUP BY n.id, n.data_cobranca, n.created_at, e.id, e.nome ORDER BY recebido_at DESC");
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql . " GROUP BY n.id, n.data_cobranca, n.created_at, e.id, e.nome ORDER BY recebido_at DESC");
        }
        
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientesSemEnvio($notinha['id']);
        }
        
        return $notinhas;
    }
    
    public function totalRecebidoMes(): float
    {
        $sql = "
            SELECT COALESCE(SUM(nr.valor), 0) as total
            FROM notinha_recebimentos nr
            JOIN notinhas n ON nr.notinha_id = n.id
            WHERE MONTH(nr.recebido_em) = MONTH(CURRENT_DATE())
            AND YEAR(nr.recebido_em) = YEAR(CURRENT_DATE())
            AND n.deleted_at IS NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND nr.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql);
        }
        
        $result = $stmt->fetch();
        return (float) $result['total'];
    }
    
    public function totalRecebidoGeral(): float
    {
        $sql = "
            SELECT COALESCE(SUM(nr.valor), 0) as total
            FROM notinha_recebimentos nr
            JOIN notinhas n ON nr.notinha_id = n.id
            WHERE n.deleted_at IS NULL
        ";
        
        if ($this->usuarioId) {
            $sql .= " AND nr.usuario_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query($sql);
        }
        
        $result = $stmt->fetch();
        return (float) $result['total'];
    }
    
    public function restaurar(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinhas SET deleted_at = NULL, inadimplente_at = NULL, recebido_at = NULL WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    
    public function excluirPermanente(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM notinhas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    
    public function iniciarTransacao(): void
    {
        $this->pdo->beginTransaction();
    }
    
    public function confirmarTransacao(): void
    {
        $this->pdo->commit();
    }
    
    public function cancelarTransacao(): void
    {
        $this->pdo->rollBack();
    }
}

