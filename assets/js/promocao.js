// ==================== PROMOÇÕES ====================
function abrirModalPromocao() {
    if (todosClientes.length === 0) {
        showToast('Cadastre clientes primeiro!', 'error');
        return;
    }
    
    const modal = document.getElementById('modal-promocao');
    const lista = document.getElementById('promocao-lista-clientes');
    
    const clientesComTelefone = todosClientes.filter(c => c.telefone);
    
    if (clientesComTelefone.length === 0) {
        showToast('Nenhum cliente com telefone cadastrado!', 'error');
        return;
    }
    
    clientesSelecionados = [];
    document.getElementById('selecionar-todos-clientes').checked = false;
    document.getElementById('promocao-mensagem').value = '';
    
    lista.innerHTML = clientesComTelefone.map(c => `
        <div class="promocao-item">
            <input type="checkbox" id="cliente-check-${c.id}" value="${c.id}" onchange="toggleClientePromocao(${c.id})">
            <div class="promocao-item-info">
                <div class="promocao-item-nome">${c.nome}</div>
                <div class="promocao-item-telefone">${formatarTelefone(c.telefone)}</div>
            </div>
        </div>
    `).join('');
    
    atualizarContadorSelecionados();
    modal.classList.add('show');
}

function fecharModalPromocao() {
    document.getElementById('modal-promocao').classList.remove('show');
}

function toggleClientePromocao(id) {
    const checkbox = document.getElementById(`cliente-check-${id}`);
    if (checkbox.checked) {
        clientesSelecionados.push(id);
    } else {
        clientesSelecionados = clientesSelecionados.filter(cid => cid !== id);
    }
    atualizarContadorSelecionados();
}

function toggleSelecionarTodosClientes() {
    const selecionarTodos = document.getElementById('selecionar-todos-clientes').checked;
    const clientesComTelefone = todosClientes.filter(c => c.telefone);
    
    clientesComTelefone.forEach(c => {
        const checkbox = document.getElementById(`cliente-check-${c.id}`);
        if (checkbox) {
            checkbox.checked = selecionarTodos;
        }
    });
    
    if (selecionarTodos) {
        clientesSelecionados = clientesComTelefone.map(c => c.id);
    } else {
        clientesSelecionados = [];
    }
    
    atualizarContadorSelecionados();
}

function atualizarContadorSelecionados() {
    document.getElementById('total-clientes-selecionados').textContent = clientesSelecionados.length;
}

function iniciarEnvioPromocao() {
    mensagemPromocao = document.getElementById('promocao-mensagem').value.trim();
    
    if (!mensagemPromocao) {
        showToast('Digite a mensagem da promoção!', 'error');
        return;
    }
    
    if (clientesSelecionados.length === 0) {
        showToast('Selecione pelo menos um cliente!', 'error');
        return;
    }
    
    filaPromocao = clientesSelecionados.map(id => todosClientes.find(c => c.id == id)).filter(c => c && c.telefone);
    indicePromocao = 0;
    
    fecharModalPromocao();
    mostrarModalEnvioPromocao();
}

function mostrarModalEnvioPromocao() {
    document.getElementById('modal-envio-promocao').classList.add('show');
    document.getElementById('btn-enviar-promocao').style.display = 'block';
    document.getElementById('btn-proximo-promocao').style.display = 'none';
    atualizarModalEnvioPromocao();
}

function atualizarModalEnvioPromocao() {
    const cliente = filaPromocao[indicePromocao];
    const total = filaPromocao.length;
    
    document.getElementById('promocao-progresso').textContent = `${indicePromocao + 1} de ${total}`;
    document.getElementById('promocao-nome').textContent = cliente.nome;
    document.getElementById('promocao-telefone').textContent = formatarTelefone(cliente.telefone);
    
    const porcentagem = ((indicePromocao + 1) / total) * 100;
    document.getElementById('promocao-barra').style.width = porcentagem + '%';
}

function enviarPromocaoAtual() {
    const cliente = filaPromocao[indicePromocao];
    const primeiroNome = cliente.nome.split(' ')[0];
    
    let msg = mensagemPromocao.replace(/{nome}/gi, primeiroNome);
    
    const telefone = cliente.telefone.replace(/\D/g, '');
    const telefoneFormatado = telefone.startsWith('55') ? telefone : '55' + telefone;
    
    const url = `whatsapp://send?phone=${telefoneFormatado}&text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
    
    document.getElementById('btn-enviar-promocao').style.display = 'none';
    document.getElementById('btn-proximo-promocao').style.display = 'block';
}

function pularPromocaoAtual() {
    indicePromocao++;
    
    if (indicePromocao < filaPromocao.length) {
        document.getElementById('btn-enviar-promocao').style.display = 'block';
        document.getElementById('btn-proximo-promocao').style.display = 'none';
        atualizarModalEnvioPromocao();
    } else {
        fecharModalEnvioPromocao();
        showToast('🎉 Promoção enviada para todos!');
    }
}

function proximaPromocao() {
    indicePromocao++;
    
    if (indicePromocao < filaPromocao.length) {
        document.getElementById('btn-enviar-promocao').style.display = 'block';
        document.getElementById('btn-proximo-promocao').style.display = 'none';
        atualizarModalEnvioPromocao();
    } else {
        fecharModalEnvioPromocao();
        showToast('🎉 Promoção enviada para todos!');
    }
}

function fecharModalEnvioPromocao() {
    document.getElementById('modal-envio-promocao').classList.remove('show');
}


