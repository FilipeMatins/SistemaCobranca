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
        $this->pdo->exec("DELETE FROM notinhas WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 15 DAY)");
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
            ORDER BY n.data_cobranca DESC, n.created_at DESC
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
            WHERE notinha_id = ?
            ORDER BY id
        ");
        $stmt->execute([$notinhaId]);
        return $stmt->fetchAll();
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
    
    public function criar(int $empresaId, string $dataCobranca): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO notinhas (empresa_id, data_cobranca) VALUES (?, ?)");
        $stmt->execute([$empresaId, $dataCobranca]);
        return (int) $this->pdo->lastInsertId();
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
        $stmt = $this->pdo->prepare("DELETE FROM notinha_clientes WHERE notinha_id = ?");
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
    
    public function restaurar(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE notinhas SET deleted_at = NULL WHERE id = ?");
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

