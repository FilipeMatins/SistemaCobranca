// ==================== EXCLUÍDOS (LIXEIRA) ====================
async function carregarExcluidos() {
    try {
        const response = await fetch('api/notinhas.php?action=excluidos');
        notinhasExcluidas = await response.json();
        renderizarExcluidos();
        
        const responseClientes = await fetch('api/notinhas.php?action=clientes_excluidos');
        clientesExcluidosGlobal = await responseClientes.json();
        renderizarClientesExcluidosGlobal();
        
        const badge = document.getElementById('badge-excluidos');
        const total = notinhasExcluidas.length + clientesExcluidosGlobal.length;
        badge.textContent = total > 0 ? total : '';
    } catch (error) {
        console.error('Erro:', error);
    }
}

function renderizarExcluidos() {
    const container = document.getElementById('excluidos-lista');

    if (notinhasExcluidas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="icon">🗑️</div>
                <p>Lixeira vazia</p>
            </div>
        `;
        return;
    }

    container.innerHTML = notinhasExcluidas.map(n => {
        const total = n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
        const clientesNomes = n.clientes.map(c => c.nome.split(' ')[0]).join(', ');
        const diasRestantes = parseInt(n.dias_restantes);
        const classeUrgencia = diasRestantes <= 3 ? 'poucos' : '';

        return `
            <div class="excluido-row">
                <div class="notinha-empresa">${n.empresa_nome}</div>
                <div class="notinha-clientes-preview">${clientesNomes}</div>
                <div class="notinha-valor">${formatarValor(total)}</div>
                <div class="dias-restantes ${classeUrgencia}">${diasRestantes} dias</div>
                <div class="notinha-acoes">
                    <button class="btn-restaurar" onclick="restaurarNotinha(${n.id})">↩️ Restaurar</button>
                    <button class="btn-excluir-perm" onclick="excluirPermanente(${n.id})">❌</button>
                </div>
            </div>
        `;
    }).join('');
}

async function restaurarNotinha(id) {
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Notinha restaurada!');
            carregarNotinhas();
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

async function excluirPermanente(id) {
    if (!confirm('Excluir PERMANENTEMENTE? Esta ação não pode ser desfeita!')) return;
    try {
        const response = await fetch(`api/notinhas.php?id=${id}&permanent=1`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            showToast('Excluído permanentemente!');
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

function renderizarClientesExcluidosGlobal() {
    const container = document.getElementById('clientes-excluidos-lista');
    
    if (clientesExcluidosGlobal.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="icon">✅</div>
                <p>Nenhum cliente removido</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = clientesExcluidosGlobal.map(c => {
        const diasRestantes = parseInt(c.dias_restantes);
        const classeUrgencia = diasRestantes <= 3 ? 'poucos' : '';
        
        return `
            <div class="cliente-excluido-lista-row">
                <div class="notinha-cliente">${c.nome}</div>
                <div class="notinha-empresa">${c.empresa_nome}</div>
                <div class="notinha-valor">${formatarValor(c.valor)}</div>
                <div class="dias-restantes ${classeUrgencia}">${diasRestantes} dias</div>
                <div class="notinha-acoes">
                    <button class="btn-restaurar" onclick="restaurarClienteGlobal(${c.id}, ${c.notinha_id})">↩️ Restaurar</button>
                    <button class="btn-excluir-perm" onclick="excluirClientePermanente(${c.id})">❌</button>
                </div>
            </div>
        `;
    }).join('');
}

async function restaurarClienteGlobal(clienteId, notinhaId) {
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restaurar_cliente', cliente_id: clienteId })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Cliente restaurado para a notinha!');
            carregarNotinhas();
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro ao restaurar', 'error');
    }
}

async function excluirClientePermanente(clienteId) {
    if (!confirm('Excluir este cliente PERMANENTEMENTE? Esta ação não pode ser desfeita!')) return;
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'excluir_cliente_permanente', cliente_id: clienteId })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Cliente excluído permanentemente!');
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro ao excluir', 'error');
    }
}


