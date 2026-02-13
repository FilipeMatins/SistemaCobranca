// ==================== HISTÓRICO DO CLIENTE ====================
async function abrirHistoricoCliente(nome, telefone) {
    try {
        const response = await fetch(`api/historico.php?nome=${encodeURIComponent(nome)}&telefone=${encodeURIComponent(telefone)}`);
        const dados = await response.json();
        
        renderizarHistorico(dados);
        document.getElementById('modal-historico').classList.add('show');
    } catch (error) {
        console.error('Erro ao carregar histórico:', error);
        showToast('Erro ao carregar histórico', 'error');
    }
}

function fecharModalHistorico() {
    document.getElementById('modal-historico').classList.remove('show');
}

function renderizarHistorico(dados) {
    // Informações do cliente
    document.getElementById('historico-nome').textContent = dados.nome || 'Cliente';
    document.getElementById('historico-telefone').textContent = dados.telefone ? '📱 ' + dados.telefone : 'Sem telefone';
    
    // Métricas
    document.getElementById('historico-total-gasto').textContent = formatarValor(dados.total_gasto || 0);
    document.getElementById('historico-media-ticket').textContent = formatarValor(dados.media_ticket || 0);
    document.getElementById('historico-total-compras').textContent = dados.total_compras || 0;
    
    // Status do pagador
    const statusEl = document.getElementById('historico-status');
    const statusContainer = document.getElementById('historico-status-container');
    
    if (dados.taxa_pagamento >= 80) {
        statusEl.textContent = '⭐';
        statusEl.className = 'metrica-valor status-bom-pagador';
        statusContainer.title = 'Bom pagador - ' + dados.taxa_pagamento.toFixed(0) + '% de pagamentos em dia';
    } else if (dados.taxa_pagamento >= 50) {
        statusEl.textContent = '⚠️';
        statusEl.className = 'metrica-valor status-regular';
        statusContainer.title = 'Pagador regular - ' + dados.taxa_pagamento.toFixed(0) + '% de pagamentos em dia';
    } else {
        statusEl.textContent = '❌';
        statusEl.className = 'metrica-valor status-mau-pagador';
        statusContainer.title = 'Atenção - ' + dados.taxa_pagamento.toFixed(0) + '% de pagamentos em dia';
    }
    
    // Lista de compras
    const listaEl = document.getElementById('historico-lista');
    
    if (!dados.compras || dados.compras.length === 0) {
        listaEl.innerHTML = `
            <div class="historico-vazio">
                <div class="historico-vazio-icon">📋</div>
                <p>Nenhuma compra registrada</p>
            </div>
        `;
        return;
    }
    
    const hoje = getHojeLocal();
    
    listaEl.innerHTML = dados.compras.map(compra => {
        let statusBadge = '';
        if (compra.msg_enviada == 1) {
            statusBadge = '<span class="historico-badge pago">Enviado</span>';
        } else if (compra.data_cobranca < hoje) {
            statusBadge = '<span class="historico-badge atrasado">Atrasado</span>';
        } else {
            statusBadge = '<span class="historico-badge pendente">Pendente</span>';
        }
        
        return `
            <div class="historico-compra-item">
                <div class="historico-compra-info">
                    <div class="historico-compra-empresa">${compra.empresa_nome}</div>
                    <div class="historico-compra-data">${formatarData(compra.data_cobranca)}</div>
                </div>
                <div class="historico-compra-valor">${formatarValor(compra.valor)}</div>
                <div class="historico-compra-status">${statusBadge}</div>
            </div>
        `;
    }).join('');
}

// Adiciona botão de histórico na lista de clientes
function renderizarListaClientesComHistorico() {
    const container = document.getElementById('clientes-cadastrados-lista');
    if (!container) return;
    
    if (clientesFiltrados.length === 0) {
        container.innerHTML = `
            <div class="clientes-vazio">
                <div class="clientes-vazio-icon">👥</div>
                <p>Nenhum cliente encontrado</p>
            </div>
        `;
        renderizarPaginacao();
        return;
    }

    const inicio = (paginaAtualClientes - 1) * clientesPorPagina;
    const fim = inicio + clientesPorPagina;
    const clientesPagina = clientesFiltrados.slice(inicio, fim);

    container.innerHTML = clientesPagina.map(c => `
        <div class="cliente-item">
            <span class="cliente-item-nome">${c.nome}</span>
            <span class="cliente-item-telefone">
                ${c.telefone ? formatarTelefone(c.telefone) : '<span class="cliente-sem-telefone">Sem telefone</span>'}
            </span>
            <div class="cliente-item-acoes">
                <button class="btn-historico" onclick="abrirHistoricoCliente('${c.nome.replace(/'/g, "\\'")}', '${c.telefone || ''}')" title="Ver histórico">📊</button>
                <button class="btn-editar" onclick="editarCliente(${c.id})" title="Editar">✏️</button>
                <button class="btn-excluir" onclick="excluirCliente(${c.id})" title="Excluir">🗑️</button>
            </div>
        </div>
    `).join('');

    renderizarPaginacao();
}


