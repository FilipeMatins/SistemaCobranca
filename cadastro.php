<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Bloco de Cobranças</title>
    <link rel="icon" type="image/png" href="assets/icons/icon-192.png">
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
        
        .cadastro-container {
            width: 100%;
            max-width: 450px;
        }
        
        .cadastro-card {
            background: rgba(30, 41, 59, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .cadastro-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .cadastro-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .cadastro-title {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .cadastro-subtitle {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            opacity: 0.6;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 42px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
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
        
        .form-group input.error {
            border-color: #ef4444;
        }
        
        .form-group input.success {
            border-color: #22c55e;
        }
        
        .input-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 4px;
        }
        
        .input-error {
            font-size: 0.75rem;
            color: #f87171;
            margin-top: 4px;
            display: none;
        }
        
        .input-error.show {
            display: block;
        }
        
        .password-strength {
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .password-strength-bar.weak { width: 33%; background: #ef4444; }
        .password-strength-bar.medium { width: 66%; background: #f59e0b; }
        .password-strength-bar.strong { width: 100%; background: #22c55e; }
        
        .password-strength-text {
            font-size: 0.7rem;
            margin-top: 4px;
            color: #64748b;
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
        
        .req-fail {
            color: #94a3b8;
        }
        
        .req-ok {
            color: #22c55e;
        }
        
        .btn-cadastrar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
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
            margin-top: 25px;
        }
        
        .btn-cadastrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.4);
        }
        
        .btn-cadastrar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-cadastrar .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }
        
        .btn-cadastrar.loading .spinner { display: block; }
        .btn-cadastrar.loading .btn-text { display: none; }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
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
        
        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .message.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }
        
        .cadastro-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cadastro-footer p {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        .cadastro-footer a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
        }
        
        .cadastro-footer a:hover {
            text-decoration: underline;
        }
        
        .termos {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 20px;
            padding: 12px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 8px;
        }
        
        .termos input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
        }
        
        .termos label {
            color: #94a3b8;
            font-size: 0.8rem;
            line-height: 1.4;
            cursor: pointer;
        }
        
        .termos a {
            color: #60a5fa;
            text-decoration: none;
        }
        
        @media (max-width: 480px) {
            .cadastro-card { padding: 25px 20px; }
            .cadastro-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <div class="cadastro-container">
        <div class="cadastro-card">
            <div class="cadastro-header">
                <span class="cadastro-icon">📝</span>
                <h1 class="cadastro-title">Criar Conta</h1>
                <p class="cadastro-subtitle">Preencha os dados para começar a usar</p>
            </div>
            
            <div class="message" id="message">
                <span id="message-icon"></span>
                <span id="message-text"></span>
            </div>
            
            <form id="cadastro-form" onsubmit="cadastrar(event)">
                <div class="form-group">
                    <label for="nome">Nome completo</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="nome" placeholder="Seu nome completo" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" id="email" placeholder="seu@email.com" required>
                    </div>
                    <span class="input-error" id="email-error"></span>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone (WhatsApp)</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📱</span>
                        <input type="tel" id="telefone" placeholder="(67) 99999-9999">
                    </div>
                    <span class="input-hint">Opcional, mas recomendado para recuperação de conta</span>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="senha" placeholder="Senha forte" required oninput="verificarForcaSenha()">
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strength-bar"></div>
                    </div>
                    <span class="password-strength-text" id="strength-text"></span>
                    <div class="password-requirements">
                        <div id="req-length" class="req-fail">✓ Mínimo 8 caracteres</div>
                        <div id="req-lower" class="req-fail">✓ Uma letra minúscula</div>
                        <div id="req-upper" class="req-fail">✓ Uma letra maiúscula</div>
                        <div id="req-number" class="req-fail">✓ Um número</div>
                        <div id="req-special" class="req-fail">✓ Um caractere especial (!@#$%...)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmar-senha">Confirmar senha</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="confirmar-senha" placeholder="Digite a senha novamente" required oninput="verificarSenhasIguais()">
                    </div>
                    <span class="input-error" id="senha-error"></span>
                </div>
                
                <div class="termos">
                    <input type="checkbox" id="aceito-termos" required>
                    <label for="aceito-termos">
                        Concordo com os <a href="#" onclick="alert('Termos: Use o sistema de forma responsável. Seus dados são protegidos.')">Termos de Uso</a> e 
                        <a href="#" onclick="alert('Privacidade: Não compartilhamos seus dados com terceiros.')">Política de Privacidade</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-cadastrar" id="btn-cadastrar">
                    <span class="spinner"></span>
                    <span class="btn-text">✨ Criar minha conta</span>
                </button>
            </form>
            
            <div class="cadastro-footer">
                <p>Já tem uma conta? <a href="login.php">Fazer login</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Verificar força da senha
        function verificarForcaSenha() {
            const senha = document.getElementById('senha').value;
            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            
            bar.className = 'password-strength-bar';
            
            if (senha.length === 0) {
                text.textContent = '';
                atualizarRequisitos(senha);
                return;
            }
            
            let forca = 0;
            if (senha.length >= 8) forca++;
            if (/[a-z]/.test(senha)) forca++;
            if (/[A-Z]/.test(senha)) forca++;
            if (/[0-9]/.test(senha)) forca++;
            if (/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(senha)) forca++;
            
            if (forca <= 2) {
                bar.classList.add('weak');
                text.textContent = 'Senha fraca';
                text.style.color = '#ef4444';
            } else if (forca <= 4) {
                bar.classList.add('medium');
                text.textContent = 'Senha média';
                text.style.color = '#f59e0b';
            } else {
                bar.classList.add('strong');
                text.textContent = 'Senha forte ✓';
                text.style.color = '#22c55e';
            }
            
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
        
        // Verificar se senhas são iguais
        function verificarSenhasIguais() {
            const senha = document.getElementById('senha').value;
            const confirmar = document.getElementById('confirmar-senha').value;
            const error = document.getElementById('senha-error');
            const input = document.getElementById('confirmar-senha');
            
            if (confirmar.length === 0) {
                error.classList.remove('show');
                input.classList.remove('error', 'success');
                return;
            }
            
            if (senha !== confirmar) {
                error.textContent = 'As senhas não conferem';
                error.classList.add('show');
                input.classList.add('error');
                input.classList.remove('success');
            } else {
                error.classList.remove('show');
                input.classList.remove('error');
                input.classList.add('success');
            }
        }
        
        // Formatar telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 0) {
                if (value.length <= 2) {
                    value = '(' + value;
                } else if (value.length <= 7) {
                    value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
                } else {
                    value = '(' + value.slice(0, 2) + ') ' + value.slice(2, 7) + '-' + value.slice(7);
                }
            }
            
            e.target.value = value;
        });
        
        // Cadastrar
        async function cadastrar(event) {
            event.preventDefault();
            
            const btn = document.getElementById('btn-cadastrar');
            const messageDiv = document.getElementById('message');
            const messageIcon = document.getElementById('message-icon');
            const messageText = document.getElementById('message-text');
            
            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const telefone = document.getElementById('telefone').value.replace(/\D/g, '');
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar-senha').value;
            const aceitoTermos = document.getElementById('aceito-termos').checked;
            
            // Validações
            if (senha !== confirmarSenha) {
                mostrarMensagem('error', '⚠️', 'As senhas não conferem');
                return;
            }
            
            if (!senhaValida(senha)) {
                mostrarMensagem('error', '⚠️', 'A senha não atende todos os requisitos de segurança');
                return;
            }
            
            if (!aceitoTermos) {
                mostrarMensagem('error', '⚠️', 'Você precisa aceitar os termos de uso');
                return;
            }
            
            // Loading
            btn.classList.add('loading');
            btn.disabled = true;
            messageDiv.classList.remove('show');
            
            try {
                const response = await fetch('api/auth.php?action=cadastrar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome, email, telefone, senha })
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    mostrarMensagem('success', '✅', 'Conta criada com sucesso! Redirecionando...');
                    setTimeout(() => {
                        window.location.href = 'login.php?cadastro=sucesso';
                    }, 2000);
                } else {
                    mostrarMensagem('error', '⚠️', data.erro || 'Erro ao criar conta');
                }
            } catch (error) {
                mostrarMensagem('error', '⚠️', 'Erro de conexão. Tente novamente.');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        }
        
        function mostrarMensagem(tipo, icon, texto) {
            const messageDiv = document.getElementById('message');
            const messageIcon = document.getElementById('message-icon');
            const messageText = document.getElementById('message-text');
            
            messageDiv.className = 'message ' + tipo + ' show';
            messageIcon.textContent = icon;
            messageText.textContent = texto;
        }
    </script>
</body>
</html>

