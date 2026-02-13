<?php
namespace App\Core;

/**
 * Classe de Autenticação
 * Gerencia sessões e verificação de login
 */
class Auth {
    
    /**
     * Iniciar sessão se ainda não iniciada
     */
    public static function iniciarSessao() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Fazer login do usuário
     */
    public static function login($usuario) {
        self::iniciarSessao();
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['logado'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerar ID da sessão por segurança
        session_regenerate_id(true);
    }
    
    /**
     * Fazer logout
     */
    public static function logout() {
        self::iniciarSessao();
        
        // Limpar variáveis de sessão
        $_SESSION = array();
        
        // Destruir cookie de sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir sessão
        session_destroy();
    }
    
    /**
     * Verificar se está logado
     */
    public static function estaLogado() {
        self::iniciarSessao();
        return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
    }
    
    /**
     * Obter ID do usuário logado
     */
    public static function getUsuarioId() {
        self::iniciarSessao();
        return $_SESSION['usuario_id'] ?? null;
    }
    
    /**
     * Obter nome do usuário logado
     */
    public static function getUsuarioNome() {
        self::iniciarSessao();
        return $_SESSION['usuario_nome'] ?? null;
    }
    
    /**
     * Obter email do usuário logado
     */
    public static function getUsuarioEmail() {
        self::iniciarSessao();
        return $_SESSION['usuario_email'] ?? null;
    }
    
    /**
     * Verificar e redirecionar se não logado (para páginas)
     */
    public static function verificarLogin($redirecionarPara = 'login.php') {
        if (!self::estaLogado()) {
            header('Location: ' . $redirecionarPara);
            exit;
        }
    }
    
    /**
     * Verificar e retornar erro se não logado (para APIs)
     */
    public static function verificarLoginAPI() {
        if (!self::estaLogado()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['erro' => 'Não autorizado. Faça login para continuar.']);
            exit;
        }
    }
}

