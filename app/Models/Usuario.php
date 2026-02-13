<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Buscar usuário por email
     */
    public function buscarPorEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar usuário por ID
     */
    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT id, nome, email, ultimo_acesso, created_at FROM usuarios WHERE id = ? AND ativo = 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar senha
     */
    public function verificarSenha($senha, $hash) {
        return password_verify($senha, $hash);
    }
    
    /**
     * Atualizar último acesso
     */
    public function atualizarUltimoAcesso($id) {
        $stmt = $this->db->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Criar novo usuário
     */
    public function criar($nome, $email, $senha) {
        // Verificar se email já existe
        $existente = $this->buscarPorEmail($email);
        if ($existente) {
            return ['erro' => 'Este email já está cadastrado'];
        }
        
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $resultado = $stmt->execute([$nome, $email, $senhaHash]);
        
        if ($resultado) {
            return ['sucesso' => true, 'id' => $this->db->lastInsertId()];
        }
        
        return ['erro' => 'Erro ao criar usuário'];
    }
    
    /**
     * Alterar senha
     */
    public function alterarSenha($id, $senhaAtual, $novaSenha) {
        // Buscar usuário
        $stmt = $this->db->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            return ['erro' => 'Usuário não encontrado'];
        }
        
        // Verificar senha atual
        if (!$this->verificarSenha($senhaAtual, $usuario['senha'])) {
            return ['erro' => 'Senha atual incorreta'];
        }
        
        // Atualizar senha
        $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $resultado = $stmt->execute([$novaSenhaHash, $id]);
        
        if ($resultado) {
            return ['sucesso' => true];
        }
        
        return ['erro' => 'Erro ao alterar senha'];
    }
    
    /**
     * Listar todos os usuários (para admin)
     */
    public function listarTodos() {
        $stmt = $this->db->query("SELECT id, nome, email, ativo, ultimo_acesso, created_at FROM usuarios ORDER BY nome");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

