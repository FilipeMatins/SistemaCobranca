<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Configuracao
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
    
    public function buscarTodas(): array
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("SELECT chave, valor FROM configuracoes WHERE usuario_id = ?");
            $stmt->execute([$this->usuarioId]);
        } else {
            $stmt = $this->pdo->query("SELECT chave, valor FROM configuracoes");
        }
        
        $configs = [];
        
        while ($row = $stmt->fetch()) {
            $configs[$row['chave']] = $row['valor'];
        }
        
        return $configs;
    }
    
    public function buscar(string $chave): ?string
    {
        if ($this->usuarioId) {
            $stmt = $this->pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ? AND usuario_id = ?");
            $stmt->execute([$chave, $this->usuarioId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
            $stmt->execute([$chave]);
        }
        $result = $stmt->fetch();
        return $result ? $result['valor'] : null;
    }
    
    public function salvar(string $chave, string $valor): void
    {
        if ($this->usuarioId) {
            // Verifica se já existe configuração para este usuário
            $stmt = $this->pdo->prepare("SELECT id FROM configuracoes WHERE chave = ? AND usuario_id = ?");
            $stmt->execute([$chave, $this->usuarioId]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                $stmt = $this->pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ? AND usuario_id = ?");
                $stmt->execute([$valor, $chave, $this->usuarioId]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO configuracoes (chave, valor, usuario_id) VALUES (?, ?, ?)");
                $stmt->execute([$chave, $valor, $this->usuarioId]);
            }
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO configuracoes (chave, valor) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");
            $stmt->execute([$chave, $valor]);
        }
    }
    
    public function salvarVarias(array $configs): void
    {
        foreach ($configs as $chave => $valor) {
            $this->salvar($chave, $valor);
        }
    }
}


