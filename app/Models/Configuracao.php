<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Configuracao
{
    private PDO $pdo;
    
    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }
    
    public function buscarTodas(): array
    {
        $stmt = $this->pdo->query("SELECT chave, valor FROM configuracoes");
        $configs = [];
        
        while ($row = $stmt->fetch()) {
            $configs[$row['chave']] = $row['valor'];
        }
        
        return $configs;
    }
    
    public function buscar(string $chave): ?string
    {
        $stmt = $this->pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
        $stmt->execute([$chave]);
        $result = $stmt->fetch();
        return $result ? $result['valor'] : null;
    }
    
    public function salvar(string $chave, string $valor): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO configuracoes (chave, valor) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");
        $stmt->execute([$chave, $valor]);
    }
    
    public function salvarVarias(array $configs): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO configuracoes (chave, valor) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");
        
        foreach ($configs as $chave => $valor) {
            $stmt->execute([$chave, $valor]);
        }
    }
}


