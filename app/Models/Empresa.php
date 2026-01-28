<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Empresa
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }
    
    public function buscar(string $termo = ''): array
    {
        if ($termo) {
            $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE nome LIKE ? ORDER BY nome LIMIT 10");
            $stmt->execute(["%$termo%"]);
        } else {
            $stmt = $this->pdo->query("SELECT id, nome FROM empresas ORDER BY nome");
        }
        
        return $stmt->fetchAll();
    }
    
    public function buscarPorNome(string $nome): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, nome FROM empresas WHERE nome = ?");
        $stmt->execute([$nome]);
        return $stmt->fetch() ?: null;
    }
    
    public function criar(string $nome): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO empresas (nome) VALUES (?)");
        $stmt->execute([$nome]);
        
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


