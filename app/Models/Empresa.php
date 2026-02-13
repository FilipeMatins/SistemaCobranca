<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Empresa
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
                $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE nome LIKE ? AND usuario_id = ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%", $this->usuarioId]);
            } else {
                $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE nome LIKE ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%"]);
            }
        } else {
            if ($this->usuarioId) {
                $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE usuario_id = ? ORDER BY nome");
                $stmt->execute([$this->usuarioId]);
            } else {
                $stmt = $this->pdo->query("SELECT id, nome FROM empresas ORDER BY nome");
            }
        }
        
        return $stmt->fetchAll();
    }
    
    public function buscarPorNome(string $nome): ?array
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE nome = ? AND usuario_id = ?");
            $stmt->execute([$nome, $this->usuarioId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE nome = ?");
            $stmt->execute([$nome]);
        }
        return $stmt->fetch() ?: null;
    }
    
    public function criar(string $nome): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO empresas (nome, usuario_id) VALUES (?, ?)");
        $stmt->execute([$nome, $this->usuarioId]);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'nome' => $nome
        ];
    }
    
    public function buscarOuCriar(string $nome): array
    {
        $empresa = $this->buscarPorNome($nome);
        
        if ($empresa) {
            return $empresa;
        }
        
        return $this->criar($nome);
    }
}


