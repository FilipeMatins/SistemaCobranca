<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Database;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Bloco de Cobranças</title>
    <link rel="icon" type="image/png" href="assets/icons/icon-192.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            padding: 20px;
        }
        
        .container { width: 100%; max-width: 420px; }
        
        .card {
            background: rgba(30, 41, 59, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header { text-align: center; margin-bottom: 30px; }
        .header-icon { font-size: 4rem; margin-bottom: 15px; display: block; }
        .header h1 { color: #fff; font-size: 1.5rem; margin-bottom: 10px; }
        .header p { color: #94a3b8; font-size: 0.9rem; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #cbd5e1; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px; }
        
        .input-wrapper { position: relative; }
        .input-wrapper .icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; opacity: 0.6; }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .form-group input::placeholder { color: #64748b; }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(34, 197, 94, 0.4); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn .spinner {
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }
        
        .btn.loading .spinner { display: block; }
        .btn.loading .btn-text { display: none; }
        
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .message {
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }
        
        .message.show { display: flex; }
        .message.error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
        .message.success { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
        
        .token-invalido {
            text-align: center;
        }
        
        .token-invalido .icon { font-size: 4rem; margin-bottom: 20px; }
        .token-invalido h2 { color: #f87171; font-size: 1.3rem; margin-bottom: 15px; }
        .token-invalido p { color: #94a3b8; font-size: 0.95rem; margin-bottom: 25px; }
        
        .btn-secondary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }
        
        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            font-size: 0.75rem;
        }
        
        .password-requirements div {
            padding: 3px 0;
            transition: all 0.2s;
        }
        
        .req-fail { color: #94a3b8; }
        .req-ok { color: #22c55e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <?php
            $token = $_GET['token'] ?? '';
            $tokenValido = false;
            
            if (!empty($token)) {
                try {
                    $db = Database::getInstance();
                    $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
                    $stmt->execute([$token]);
                    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($reset) {
                        $tokenValido = true;
                    }
                } catch (Exception $e) {
                    // Token inválido
                }
            }
            
            if (!$tokenValido):
            ?>
            <!-- Token inválido ou expirado -->
            <div class="token-invalido">
                <div class="icon">⚠️</div>
                <h2>Link inválido ou expirado</h2>
                <p>Este link de recuperação de senha não é válido ou já expirou. Solicite um novo link.</p>
                <a href="esqueci-senha.php" class="btn btn-secondary">Solicitar novo link</a>
            </div>
            <?php else: ?>
            <!-- Formulário de nova senha -->
            <div class="header">
                <span class="header-icon">🔑</span>
                <h1>Criar nova senha</h1>
                <p>Digite sua nova senha abaixo</p>
            </div>
            
            <div class="message" id="message">
                <span id="message-icon"></span>
                <span id="message-text"></span>
            </div>
            
            <form onsubmit="redefinirSenha(event)">
                <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="nova-senha">Nova senha</label>
                    <div class="input-wrapper">
                        <span class="icon">🔒</span>
                        <input type="password" id="nova-senha" placeholder="Senha forte" required oninput="verificarForcaSenha()">
                    </div>
                    <div class="password-requirements">
                        <div id="req-length" class="req-fail">✓ Mínimo 8 caracteres</div>
                        <div id="req-lower" class="req-fail">✓ Uma letra minúscula</div>
                        <div id="req-upper" class="req-fail">✓ Uma letra maiúscula</div>
                        <div id="req-number" class="req-fail">✓ Um número</div>
                        <div id="req-special" class="req-fail">✓ Um caractere especial (!@#$%...)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmar-senha">Confirmar nova senha</label>
                    <div class="input-wrapper">
                        <span class="icon">🔒</span>
                        <input type="password" id="confirmar-senha" placeholder="Digite novamente" required>
                    </div>
                </div>
                
                <button type="submit" class="btn" id="btn-redefinir">
                    <span class="spinner"></span>
                    <span class="btn-text">✅ Salvar nova senha</span>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        async function redefinirSenha(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btn-redefinir');
            const token = document.getElementById('token').value;
            const novaSenha = document.getElementById('nova-senha').value;
            const confirmarSenha = document.getElementById('confirmar-senha').value;
            
            if (novaSenha !== confirmarSenha) {
                mostrarMensagem('error', '⚠️', 'As senhas não conferem');
                return;
            }
            
            if (!senhaValida(novaSenha)) {
                mostrarMensagem('error', '⚠️', 'A senha não atende todos os requisitos de segurança');
                return;
            }
            
            btn.classList.add('loading');
            btn.disabled = true;
            
            try {
                const response = await fetch('api/auth.php?action=redefinir-senha', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, nova_senha: novaSenha })
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    mostrarMensagem('success', '✅', 'Senha alterada com sucesso! Redirecionando...');
                    setTimeout(() => {
                        window.location.href = 'login.php?senha=alterada';
                    }, 2000);
                } else {
                    mostrarMensagem('error', '⚠️', data.erro || 'Erro ao redefinir senha');
                }
            } catch (error) {
                mostrarMensagem('error', '⚠️', 'Erro de conexão. Tente novamente.');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }
        
        function mostrarMensagem(tipo, icon, texto) {
            const div = document.getElementById('message');
            div.className = 'message ' + tipo + ' show';
            document.getElementById('message-icon').textContent = icon;
            document.getElementById('message-text').textContent = texto;
        }
        
        // Verificar força da senha
        function verificarForcaSenha() {
            const senha = document.getElementById('nova-senha').value;
            atualizarRequisitos(senha);
        }
        
        // Atualizar indicadores de requisitos
        function atualizarRequisitos(senha) {
            document.getElementById('req-length').className = senha.length >= 8 ? 'req-ok' : 'req-fail';
            document.getElementById('req-lower').className = /[a-z]/.test(senha) ? 'req-ok' : 'req-fail';
            document.getElementById('req-upper').className = /[A-Z]/.test(senha) ? 'req-ok' : 'req-fail';
            document.getElementById('req-number').className = /[0-9]/.test(senha) ? 'req-ok' : 'req-fail';
            document.getElementById('req-special').className = /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(senha) ? 'req-ok' : 'req-fail';
        }
        
        // Validar senha completa
        function senhaValida(senha) {
            return senha.length >= 8 &&
                   /[a-z]/.test(senha) &&
                   /[A-Z]/.test(senha) &&
                   /[0-9]/.test(senha) &&
                   /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(senha);
        }
    </script>
</body>
</html>

