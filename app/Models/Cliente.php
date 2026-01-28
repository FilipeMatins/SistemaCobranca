<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Cliente
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }
    
    public function buscar(string $termo = ''): array
    {
        if ($termo) {
            $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? ORDER BY nome LIMIT 10");
            $stmt->execute(["%$termo%"]);
        } else {
            $stmt = $this->pdo->query("SELECT id, nome, telefone FROM clientes ORDER BY nome");
        }
        
        return $stmt->fetchAll();
    }
    
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public function buscarPorNome(string $nome): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome = ?");
        $stmt->execute([$nome]);
        return $stmt->fetch() ?: null;
    }
    
    public function buscarPorTelefone(string $telefone, int $excluirId = 0): ?array
    {
        if ($excluirId > 0) {
            $stmt = $this->pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ? AND id != ?");
            $stmt->execute([$telefone, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ?");
            $stmt->execute([$telefone]);
        }
        return $stmt->fetch() ?: null;
    }
    
    public function criar(string $nome, string $telefone = ''): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
        $stmt->execute([$nome, $telefone]);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'nome' => $nome,
            'telefone' => $telefone
        ];
    }
    
    public function atualizar(int $id, string $nome, string $telefone = ''): bool
    {
        $stmt = $this->pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE id = ?");
        return $stmt->execute([$nome, $telefone, $id]);
    }
    
    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function salvarOuAtualizar(string $nome, string $telefone): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO clientes (nome, telefone) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE telefone = VALUES(telefone)
        ");
        $stmt->execute([$nome, $telefone]);
    }
}


