<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Bloco de Cobranças</title>
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
        .header p { color: #94a3b8; font-size: 0.9rem; line-height: 1.5; }
        
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
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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
        
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4); }
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
        
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer a { color: #60a5fa; text-decoration: none; font-weight: 500; }
        .footer a:hover { text-decoration: underline; }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box p { color: #94a3b8; font-size: 0.85rem; line-height: 1.5; }
        .info-box strong { color: #60a5fa; }
        
        /* Etapa de sucesso */
        .success-container { text-align: center; }
        .success-icon { font-size: 4rem; margin-bottom: 20px; }
        .success-title { color: #4ade80; font-size: 1.3rem; margin-bottom: 15px; }
        .success-text { color: #94a3b8; font-size: 0.95rem; line-height: 1.6; margin-bottom: 25px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Etapa 1: Solicitar email -->
            <div id="etapa-email">
                <div class="header">
                    <span class="header-icon">🔐</span>
                    <h1>Esqueceu sua senha?</h1>
                    <p>Digite seu email e enviaremos instruções para redefinir sua senha.</p>
                </div>
                
                <div class="message" id="message">
                    <span id="message-icon"></span>
                    <span id="message-text"></span>
                </div>
                
                <form onsubmit="solicitarRecuperacao(event)">
                    <div class="form-group">
                        <label for="email">Email cadastrado</label>
                        <div class="input-wrapper">
                            <span class="icon">📧</span>
                            <input type="email" id="email" placeholder="seu@email.com" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" id="btn-enviar">
                        <span class="spinner"></span>
                        <span class="btn-text">📤 Enviar link de recuperação</span>
                    </button>
                </form>
                
                <div class="footer">
                    <a href="login.php">← Voltar para o login</a>
                </div>
            </div>
            
            <!-- Etapa 2: Email enviado -->
            <div id="etapa-sucesso" style="display: none;">
                <div class="success-container">
                    <div class="success-icon">📬</div>
                    <h2 class="success-title">Email enviado!</h2>
                    <p class="success-text">
                        Enviamos um link de recuperação para<br>
                        <strong id="email-enviado"></strong><br><br>
                        Verifique sua caixa de entrada e spam.<br>
                        O link expira em <strong>1 hora</strong>.
                    </p>
                    
                    <div class="info-box">
                        <p>💡 <strong>Não recebeu?</strong> Verifique a pasta de spam ou aguarde alguns minutos e tente novamente.</p>
                    </div>
                    
                    <button class="btn" onclick="window.location.href='login.php'">
                        ← Voltar para o login
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        async function solicitarRecuperacao(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btn-enviar');
            const email = document.getElementById('email').value.trim();
            
            btn.classList.add('loading');
            btn.disabled = true;
            esconderMensagem();
            
            try {
                const response = await fetch('api/auth.php?action=recuperar-senha', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    document.getElementById('etapa-email').style.display = 'none';
                    document.getElementById('etapa-sucesso').style.display = 'block';
                    document.getElementById('email-enviado').textContent = email;
                } else {
                    mostrarMensagem('error', '⚠️', data.erro || 'Erro ao processar solicitação');
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
        
        function esconderMensagem() {
            document.getElementById('message').classList.remove('show');
        }
    </script>
</body>
</html>


