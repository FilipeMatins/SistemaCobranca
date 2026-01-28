<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Notinha
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance();
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
        $stmt = $this->pdo->query("
            SELECT 
                n.id,
                n.data_cobranca,
                n.enviada,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome
            FROM notinhas n
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.deleted_at IS NULL 
            AND n.inadimplente_at IS NULL 
            AND n.recebido_at IS NULL
            ORDER BY n.data_cobranca DESC, n.created_at DESC
        ");
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientes($notinha['id']);
        }
        
        return $notinhas;
    }
    
    public function listarInadimplentes(): array
    {
        $stmt = $this->pdo->query("
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
            ORDER BY n.inadimplente_at DESC
        ");
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientes($notinha['id']);
        }
        
        return $notinhas;
    }
    
    public function listarExcluidas(): array
    {
        $stmt = $this->pdo->query("
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
            ORDER BY n.deleted_at DESC
        ");
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
        return $stmt->fetchAll();
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
        return $stmt->fetchAll();
    }
    
    public function listarTodosClientesExcluidos(): array
    {
        $stmt = $this->pdo->query("
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
            ORDER BY nc.deleted_at DESC
        ");
        return $stmt->fetchAll();
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
        return $stmt->fetchAll();
    }
    
    public function criar(int $empresaId, string $dataCobranca, int $numeroParcela = 1, int $totalParcelas = 1, ?int $parcelaOrigem = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO notinhas (empresa_id, data_cobranca, numero_parcela, total_parcelas, parcela_origem_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$empresaId, $dataCobranca, $numeroParcela, $totalParcelas, $parcelaOrigem]);
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
                'telefone' => $c['telefone'] ?? ''
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
    
    public function listarRecebidos(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                n.id,
                n.data_cobranca,
                n.recebido_at,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome
            FROM notinhas n
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.recebido_at IS NOT NULL AND n.deleted_at IS NULL
            ORDER BY n.recebido_at DESC
        ");
        $notinhas = $stmt->fetchAll();
        
        foreach ($notinhas as &$notinha) {
            $notinha['clientes'] = $this->buscarClientesSemEnvio($notinha['id']);
        }
        
        return $notinhas;
    }
    
    public function totalRecebidoMes(): float
    {
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(nc.valor), 0) as total
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            WHERE n.recebido_at IS NOT NULL 
            AND MONTH(n.recebido_at) = MONTH(CURRENT_DATE())
            AND YEAR(n.recebido_at) = YEAR(CURRENT_DATE())
        ");
        $result = $stmt->fetch();
        return (float) $result['total'];
    }
    
    public function totalRecebidoGeral(): float
    {
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(nc.valor), 0) as total
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            WHERE n.recebido_at IS NOT NULL
        ");
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

