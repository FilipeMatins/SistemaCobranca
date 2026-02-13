<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Cliente
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
    
    public function buscar(string $termo = ''): array
    {
        if ($termo) {
            if ($this->usuarioId) {
                $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? AND usuario_id = ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%", $this->usuarioId]);
            } else {
                $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%"]);
            }
        } else {
            if ($this->usuarioId) {
                $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE usuario_id = ? ORDER BY nome");
                $stmt->execute([$this->usuarioId]);
            } else {
                $stmt = $this->pdo->query("SELECT id, nome, telefone FROM clientes ORDER BY nome");
            }
        }
        
        return $stmt->fetchAll();
    }
    
    public function buscarPorId(int $id): ?array
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $this->usuarioId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE id = ?");
            $stmt->execute([$id]);
        }
        return $stmt->fetch() ?: null;
    }
    
    public function buscarPorNome(string $nome): ?array
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome = ? AND usuario_id = ?");
            $stmt->execute([$nome, $this->usuarioId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome = ?");
            $stmt->execute([$nome]);
        }
        return $stmt->fetch() ?: null;
    }
    
    public function buscarPorTelefone(string $telefone, int $excluirId = 0): ?array
    {
        if ($this->usuarioId) {
            if ($excluirId > 0) {
                $stmt = $this->pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ? AND id != ? AND usuario_id = ?");
                $stmt->execute([$telefone, $excluirId, $this->usuarioId]);
            } else {
                $stmt = $this->pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ? AND usuario_id = ?");
                $stmt->execute([$telefone, $this->usuarioId]);
            }
        } else {
            if ($excluirId > 0) {
                $stmt = $this->pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ? AND id != ?");
                $stmt->execute([$telefone, $excluirId]);
            } else {
                $stmt = $this->pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ?");
                $stmt->execute([$telefone]);
            }
        }
        return $stmt->fetch() ?: null;
    }
    
    public function criar(string $nome, string $telefone = ''): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO clientes (nome, telefone, usuario_id) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $telefone, $this->usuarioId]);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'nome' => $nome,
            'telefone' => $telefone
        ];
    }
    
    public function atualizar(int $id, string $nome, string $telefone = ''): bool
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE id = ? AND usuario_id = ?");
            return $stmt->execute([$nome, $telefone, $id, $this->usuarioId]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE id = ?");
            return $stmt->execute([$nome, $telefone, $id]);
        }
    }
    
    public function excluir(int $id): bool
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE id = ? AND usuario_id = ?");
            return $stmt->execute([$id, $this->usuarioId]);
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM clientes WHERE id = ?");
            return $stmt->execute([$id]);
        }
    }
    
    public function salvarOuAtualizar(string $nome, string $telefone): void
    {
        if ($this->usuarioId) {
            // Verifica se já existe com este nome para este usuário
            $stmt = $this->pdo->prepare("SELECT id FROM clientes WHERE nome = ? AND usuario_id = ?");
            $stmt->execute([$nome, $this->usuarioId]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                $stmt = $this->pdo->prepare("UPDATE clientes SET telefone = ? WHERE id = ?");
                $stmt->execute([$telefone, $existe['id']]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO clientes (nome, telefone, usuario_id) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $telefone, $this->usuarioId]);
            }
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO clientes (nome, telefone) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE telefone = VALUES(telefone)
            ");
            $stmt->execute([$nome, $telefone]);
        }
    }
}


