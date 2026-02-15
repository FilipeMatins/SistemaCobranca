<?php
/**
 * API de Autenticação
 * Endpoints: login, logout, verificar, alterar-senha, cadastrar, recuperar-senha, redefinir-senha
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Core\Auth;
use App\Core\Email;
use App\Models\Usuario;

header('Content-Type: application/json; charset=utf-8');

/**
 * Validar força da senha
 * Retorna mensagem de erro ou null se válida
 */
function validarForcaSenha($senha) {
    if (strlen($senha) < 8) {
        return 'A senha deve ter pelo menos 8 caracteres';
    }
    
    if (!preg_match('/[a-z]/', $senha)) {
        return 'A senha deve conter pelo menos uma letra minúscula';
    }
    
    if (!preg_match('/[A-Z]/', $senha)) {
        return 'A senha deve conter pelo menos uma letra maiúscula';
    }
    
    if (!preg_match('/[0-9]/', $senha)) {
        return 'A senha deve conter pelo menos um número';
    }
    
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $senha)) {
        return 'A senha deve conter pelo menos um caractere especial (!@#$%^&*...)';
    }
    
    return null; // Senha válida
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $usuario = new Usuario();
    $db = Database::getInstance();
    
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            $email = trim($dados['email'] ?? '');
            $senha = $dados['senha'] ?? '';
            
            if (empty($email) || empty($senha)) {
                echo json_encode(['erro' => 'Email e senha são obrigatórios']);
                exit;
            }
            
            // Buscar usuário
            $user = $usuario->buscarPorEmail($email);
            
            if (!$user) {
                echo json_encode(['erro' => 'Email ou senha incorretos']);
                exit;
            }
            
            // Verificar senha
            if (!$usuario->verificarSenha($senha, $user['senha'])) {
                echo json_encode(['erro' => 'Email ou senha incorretos']);
                exit;
            }
            
            // Fazer login
            Auth::login($user);
            
            // Atualizar último acesso
            $usuario->atualizarUltimoAcesso($user['id']);
            
            echo json_encode([
                'sucesso' => true,
                'usuario' => [
                    'id' => $user['id'],
                    'nome' => $user['nome'],
                    'email' => $user['email']
                ]
            ]);
            break;
            
        case 'logout':
            Auth::logout();
            echo json_encode(['sucesso' => true]);
            break;
            
        case 'verificar':
            if (Auth::estaLogado()) {
                echo json_encode([
                    'logado' => true,
                    'usuario' => [
                        'id' => Auth::getUsuarioId(),
                        'nome' => Auth::getUsuarioNome(),
                        'email' => Auth::getUsuarioEmail()
                    ]
                ]);
            } else {
                echo json_encode(['logado' => false]);
            }
            break;
            
        case 'alterar-senha':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            Auth::verificarLoginAPI();
            
            $dados = json_decode(file_get_contents('php://input'), true);
            $senhaAtual = $dados['senha_atual'] ?? '';
            $novaSenha = $dados['nova_senha'] ?? '';
            
            if (empty($senhaAtual) || empty($novaSenha)) {
                echo json_encode(['erro' => 'Senha atual e nova senha são obrigatórias']);
                exit;
            }
            
            // Validar força da senha
            $erroSenha = validarForcaSenha($novaSenha);
            if ($erroSenha) {
                echo json_encode(['erro' => $erroSenha]);
                exit;
            }

            $resultado = $usuario->alterarSenha(Auth::getUsuarioId(), $senhaAtual, $novaSenha);
            echo json_encode($resultado);
            break;
        
        case 'cadastrar':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            $nome = trim($dados['nome'] ?? '');
            $email = trim($dados['email'] ?? '');
            $telefone = trim($dados['telefone'] ?? '');
            $senha = $dados['senha'] ?? '';
            
            // Validações
            if (empty($nome) || empty($email) || empty($senha)) {
                echo json_encode(['erro' => 'Nome, email e senha são obrigatórios']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['erro' => 'Email inválido']);
                exit;
            }
            
            // Validar força da senha
            $erroSenha = validarForcaSenha($senha);
            if ($erroSenha) {
                echo json_encode(['erro' => $erroSenha]);
                exit;
            }
            
            // Verificar se email já existe
            $existente = $usuario->buscarPorEmail($email);
            if ($existente) {
                echo json_encode(['erro' => 'Este email já está cadastrado']);
                exit;
            }
            
            // Criar usuário
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nome, email, telefone, senha) VALUES (?, ?, ?, ?)");
            $resultado = $stmt->execute([$nome, $email, $telefone, $senhaHash]);
            
            if ($resultado) {
                $novoId = (int) $db->lastInsertId();
                
                // Criar configurações padrão para o novo usuário (tabela usa chave/valor)
                $mensagemPadrao = 'Olá {nome}! Aqui é {vendedor}, passando para lembrar do pagamento de {valor}. Chave PIX: {pix}';
                $stmtConfig = $db->prepare("INSERT INTO configuracoes (usuario_id, chave, valor) VALUES (?, 'chave_pix', ''), (?, 'nome_vendedor', ''), (?, 'mensagem_padrao', ?)");
                $stmtConfig->execute([$novoId, $novoId, $novoId, $mensagemPadrao]);
                
                // Fazer login automático para que as notinhas/dados sejam salvos na conta dela
                $novoUsuario = ['id' => $novoId, 'nome' => $nome, 'email' => $email];
                Auth::login($novoUsuario);
                
                echo json_encode([
                    'sucesso' => true,
                    'id' => $novoId,
                    'usuario' => ['id' => $novoId, 'nome' => $nome, 'email' => $email]
                ]);
            } else {
                echo json_encode(['erro' => 'Erro ao criar conta']);
            }
            break;
        
        case 'recuperar-senha':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            $email = trim($dados['email'] ?? '');
            
            if (empty($email)) {
                echo json_encode(['erro' => 'Email é obrigatório']);
                exit;
            }
            
            // Verificar se usuário existe
            $user = $usuario->buscarPorEmail($email);
            
            // Por segurança, sempre retornamos sucesso mesmo se o email não existir
            // Isso evita que alguém descubra quais emails estão cadastrados
            if ($user) {
                // Gerar token único
                $token = bin2hex(random_bytes(32));
                $expiraEm = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Invalidar tokens anteriores
                $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE email = ?");
                $stmt->execute([$email]);
                
                // Salvar novo token
                $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $token, $expiraEm]);
                
                // Montar link de recuperação
                $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $pasta = dirname($_SERVER['REQUEST_URI']);
                $pasta = str_replace('/api', '', $pasta);
                $linkRecuperacao = "{$protocolo}://{$host}{$pasta}/redefinir-senha.php?token={$token}";
                
                // Enviar email
                require_once __DIR__ . '/../app/Core/Email.php';
                $emailService = new Email();
                $resultado = $emailService->enviarRecuperacaoSenha($email, $user['nome'], $linkRecuperacao);
                
                // Log para debug (pode remover depois)
                $logFile = __DIR__ . '/../logs/recuperacao_senha.log';
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Email: {$email} | Link: {$linkRecuperacao} | Resultado: " . json_encode($resultado) . "\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
            }
            
            echo json_encode(['sucesso' => true]);
            break;
        
        case 'redefinir-senha':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $dados = json_decode(file_get_contents('php://input'), true);
            $token = trim($dados['token'] ?? '');
            $novaSenha = $dados['nova_senha'] ?? '';
            
            if (empty($token) || empty($novaSenha)) {
                echo json_encode(['erro' => 'Token e nova senha são obrigatórios']);
                exit;
            }
            
            // Validar força da senha
            $erroSenha = validarForcaSenha($novaSenha);
            if ($erroSenha) {
                echo json_encode(['erro' => $erroSenha]);
                exit;
            }
            
            // Verificar token
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset) {
                echo json_encode(['erro' => 'Link inválido ou expirado']);
                exit;
            }
            
            // Atualizar senha
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
            $resultado = $stmt->execute([$senhaHash, $reset['email']]);
            
            if ($resultado) {
                // Marcar token como usado
                $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->execute([$token]);
                
                echo json_encode(['sucesso' => true]);
            } else {
                echo json_encode(['erro' => 'Erro ao redefinir senha']);
            }
            break;
            
        default:
            echo json_encode(['erro' => 'Ação não encontrada']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}

