<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bloco de Cobranças</title>
    <link rel="icon" type="image/png" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(30, 41, 59, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .login-title {
            color: #fff;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            opacity: 0.6;
        }
        
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
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .form-group input::placeholder {
            color: #64748b;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-login .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }
        
        .btn-login.loading .spinner {
            display: block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }
        
        .error-message.show {
            display: flex;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-footer p {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        /* Animação de entrada */
        .login-card {
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsivo */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .login-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <span class="login-icon">📝</span>
                <h1 class="login-title">Bloco de Cobranças</h1>
                <p class="login-subtitle">Faça login para acessar o sistema</p>
            </div>
            
            <div class="error-message" id="error-message">
                <span>⚠️</span>
                <span id="error-text"></span>
            </div>
            
            <form id="login-form" onsubmit="fazerLogin(event)">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="seu@email.com"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="btn-login">
                    <span class="spinner"></span>
                    <span class="btn-text">🚀 Entrar</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p><a href="esqueci-senha.php">Esqueci minha senha</a></p>
                <p style="margin-top: 15px;">Não tem conta? <a href="cadastro.php">Criar conta grátis</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Verificar se já está logado
        async function verificarSessao() {
            try {
                const response = await fetch('api/auth.php?action=verificar');
                const data = await response.json();
                
                if (data.logado) {
                    window.location.href = 'index.php';
                }
            } catch (e) {
                console.error('Erro ao verificar sessão:', e);
            }
        }
        
        verificarSessao();
        
        // Fazer login
        async function fazerLogin(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btn-login');
            const errorDiv = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            
            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value;
            
            // Esconder erro anterior
            errorDiv.classList.remove('show');
            
            // Mostrar loading
            btn.classList.add('loading');
            btn.disabled = true;
            
            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, senha })
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    // Redirecionar para o sistema
                    window.location.href = 'index.php';
                } else {
                    // Mostrar erro
                    errorText.textContent = data.erro || 'Erro ao fazer login';
                    errorDiv.classList.add('show');
                }
            } catch (error) {
                errorText.textContent = 'Erro de conexão. Tente novamente.';
                errorDiv.classList.add('show');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }
        
        // Enter para enviar
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('login-form').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>

