// ==================== AUTENTICAÇÃO ====================

/**
 * Fazer logout do sistema
 */
async function fazerLogout() {
    if (!confirm('Deseja realmente sair do sistema?')) {
        return;
    }
    
    try {
        const response = await fetch('api/auth.php?action=logout');
        const data = await response.json();
        
        if (data.sucesso) {
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
        // Redirecionar mesmo assim
        window.location.href = 'login.php';
    }
}

/**
 * Verificar se a sessão ainda é válida
 */
async function verificarSessaoAtiva() {
    try {
        const response = await fetch('api/auth.php?action=verificar');
        const data = await response.json();
        
        if (!data.logado) {
            // Sessão expirou, redirecionar para login
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Erro ao verificar sessão:', error);
    }
}

/**
 * Abrir modal para alterar senha
 */
function abrirModalAlterarSenha() {
    const modal = document.getElementById('modal-alterar-senha');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('senha-atual').value = '';
        document.getElementById('nova-senha').value = '';
        document.getElementById('confirmar-senha').value = '';
    }
}

/**
 * Fechar modal de alterar senha
 */
function fecharModalAlterarSenha() {
    const modal = document.getElementById('modal-alterar-senha');
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Validar força da senha
 */
function senhaForteValida(senha) {
    return senha.length >= 8 &&
           /[a-z]/.test(senha) &&
           /[A-Z]/.test(senha) &&
           /[0-9]/.test(senha) &&
           /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(senha);
}

/**
 * Verificar força da senha no modal de alterar senha
 */
function verificarForcaSenhaModal() {
    const senha = document.getElementById('nova-senha').value;
    
    const setClass = (id, condition) => {
        const el = document.getElementById(id);
        if (el) el.className = condition ? 'req-ok' : 'req-fail';
    };
    
    setClass('modal-req-length', senha.length >= 8);
    setClass('modal-req-lower', /[a-z]/.test(senha));
    setClass('modal-req-upper', /[A-Z]/.test(senha));
    setClass('modal-req-number', /[0-9]/.test(senha));
    setClass('modal-req-special', /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(senha));
}

/**
 * Alterar senha do usuário
 */
async function alterarSenha() {
    const senhaAtual = document.getElementById('senha-atual').value;
    const novaSenha = document.getElementById('nova-senha').value;
    const confirmarSenha = document.getElementById('confirmar-senha').value;
    
    // Validações
    if (!senhaAtual || !novaSenha || !confirmarSenha) {
        showToast('Preencha todos os campos', 'error');
        return;
    }
    
    if (novaSenha !== confirmarSenha) {
        showToast('As senhas não conferem', 'error');
        return;
    }
    
    if (!senhaForteValida(novaSenha)) {
        showToast('A senha deve ter: 8+ caracteres, maiúscula, minúscula, número e caractere especial', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/auth.php?action=alterar-senha', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                senha_atual: senhaAtual,
                nova_senha: novaSenha
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            showToast('Senha alterada com sucesso!', 'success');
            fecharModalAlterarSenha();
        } else {
            showToast(data.erro || 'Erro ao alterar senha', 'error');
        }
    } catch (error) {
        console.error('Erro ao alterar senha:', error);
        showToast('Erro ao alterar senha', 'error');
    }
}

// Verificar sessão periodicamente (a cada 5 minutos)
setInterval(verificarSessaoAtiva, 5 * 60 * 1000);

