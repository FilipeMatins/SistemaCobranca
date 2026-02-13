// ==================== EDIÇÃO DE NOTINHA ====================
function abrirEdicao(notinha) {
    notinhaIdEdicao = notinha.id;
    notinhaEdicaoAtual = notinha; // Guarda notinha completa
    document.getElementById('editar-id').value = notinha.id;
    document.getElementById('editar-empresa').value = notinha.empresa_nome;
    document.getElementById('editar-data').value = notinha.data_cobranca;
    
    clientesEdicao = notinha.clientes.map(c => ({
        id: c.id,
        nome: c.nome,
        valor: parseFloat(c.valor).toFixed(2).replace('.', ','),
        valorNumerico: parseFloat(c.valor),
        telefone: c.telefone,
        msg_enviada: c.msg_enviada,
        existente: true
    }));
    
    renderizarClientesEdicao();
    carregarClientesExcluidosEdicao();
    document.getElementById('modal-editar').classList.add('show');
}

function fecharEdicao() {
    document.getElementById('modal-editar').classList.remove('show');
    notinhaIdEdicao = null;
}

function adicionarClienteEdicao() {
    clientesEdicao.push({ id: 'novo_' + Date.now(), nome: '', valor: '', telefone: '', existente: false });
    renderizarClientesEdicao();
}

async function removerClienteEdicao(id, existente) {
    if (existente) {
        try {
            const response = await fetch('api/notinhas.php', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'excluir_cliente', cliente_id: id })
            });
            const result = await response.json();
            if (result.success) {
                showToast('Cliente movido para lixeira!');
                clientesEdicao = clientesEdicao.filter(c => c.id != id);
                renderizarClientesEdicao();
                carregarClientesExcluidosEdicao();
                carregarExcluidos();
            } else {
                showToast(result.error || 'Erro ao remover', 'error');
            }
        } catch (error) {
            showToast('Erro ao remover', 'error');
            console.error('Erro:', error);
        }
    } else {
        clientesEdicao = clientesEdicao.filter(c => c.id != id);
        renderizarClientesEdicao();
    }
}

async function carregarClientesExcluidosEdicao() {
    if (!notinhaIdEdicao) return;
    
    try {
        const response = await fetch(`api/notinhas.php?action=clientes_excluidos&notinha_id=${notinhaIdEdicao}`);
        const excluidos = await response.json();
        renderizarClientesExcluidosEdicao(excluidos);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function renderizarClientesExcluidosEdicao(excluidos) {
    const container = document.getElementById('clientes-excluidos-edicao');
    if (!container) return;
    
    if (excluidos.length === 0) {
        container.innerHTML = '';
        container.style.display = 'none';
        return;
    }
    
    container.style.display = 'block';
    container.innerHTML = `
        <div class="clientes-excluidos-titulo">🗑️ Clientes Removidos (${excluidos.length})</div>
        ${excluidos.map(c => `
            <div class="cliente-excluido-row">
                <span class="cliente-excluido-nome">${c.nome}</span>
                <span class="cliente-excluido-valor">${formatarValor(c.valor)}</span>
                <button class="btn-restaurar-cliente" onclick="restaurarClienteEdicao(${c.id})">↩️ Restaurar</button>
            </div>
        `).join('')}
    `;
}

async function restaurarClienteEdicao(clienteId) {
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restaurar_cliente', cliente_id: clienteId })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Cliente restaurado!');
            carregarNotinhas().then(() => {
                const notinha = todasNotinhas.find(n => n.id == notinhaIdEdicao);
                if (notinha) {
                    clientesEdicao = notinha.clientes.map(c => ({
                        id: c.id,
                        nome: c.nome,
                        valor: parseFloat(c.valor).toFixed(2).replace('.', ','),
                        telefone: c.telefone,
                        existente: true
                    }));
                    renderizarClientesEdicao();
                    carregarClientesExcluidosEdicao();
                }
            });
        }
    } catch (error) {
        showToast('Erro ao restaurar', 'error');
    }
}

function atualizarClienteEdicao(id, campo, valor) {
    const cliente = clientesEdicao.find(c => c.id == id);
    if (cliente) cliente[campo] = valor;
}

function onNomeInputEdicao(id, valor) {
    atualizarClienteEdicao(id, 'nome', valor);
    if (valor.length >= 2) {
        buscarClientesEdicao(valor, id);
    } else {
        const lista = document.getElementById(`autocomplete-edicao-${id}`);
        if (lista) lista.classList.remove('show');
    }
}

async function buscarClientesEdicao(termo, clienteId) {
    try {
        const response = await fetch(`api/clientes.php?termo=${encodeURIComponent(termo)}`);
        const clientesCadastrados = await response.json();
        mostrarAutocompleteEdicao(clientesCadastrados, clienteId);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function mostrarAutocompleteEdicao(clientesCadastrados, clienteId) {
    const lista = document.getElementById(`autocomplete-edicao-${clienteId}`);
    if (!lista) return;
    if (clientesCadastrados.length === 0) {
        lista.classList.remove('show');
        return;
    }
    
    lista.innerHTML = clientesCadastrados.map(c => `
        <div class="autocomplete-item" onclick="selecionarClienteEdicao('${clienteId}', '${c.nome.replace(/'/g, "\\'")}', '${c.telefone || ''}')">
            <span class="nome">${c.nome}</span>
            ${c.telefone ? `<span class="telefone">${c.telefone}</span>` : ''}
        </div>
    `).join('');
    lista.classList.add('show');
}

function selecionarClienteEdicao(clienteId, nome, telefone) {
    const cliente = clientesEdicao.find(c => c.id == clienteId);
    if (cliente) {
        cliente.nome = nome;
        cliente.telefone = telefone;
    }
    renderizarClientesEdicao();
    
    const lista = document.getElementById(`autocomplete-edicao-${clienteId}`);
    if (lista) lista.classList.remove('show');
}

function renderizarClientesEdicao() {
    document.getElementById('editar-clientes-lista').innerHTML = clientesEdicao.map(c => {
        const isExistente = c.existente || false;
        const valorFormatado = c.valorNumerico ? c.valorNumerico : c.valor.replace(',', '.');
        
        return `
        <div class="cliente-row-edicao">
            <div class="cliente-row">
                <div class="nome-wrapper">
                    <input type="text" id="edicao-nome-${c.id}" placeholder="Nome" value="${c.nome}" 
                           oninput="onNomeInputEdicao('${c.id}', this.value)" autocomplete="off">
                    <div class="autocomplete-list" id="autocomplete-edicao-${c.id}"></div>
                </div>
                <input type="text" class="valor" placeholder="R$ 0,00" value="${c.valor}"
                       oninput="atualizarClienteEdicao('${c.id}', 'valor', this.value)">
                <input type="text" placeholder="Telefone" value="${c.telefone}"
                       oninput="atualizarClienteEdicao('${c.id}', 'telefone', this.value)">
                <button class="btn-remove" onclick="removerClienteEdicao('${c.id}', ${isExistente})" title="Excluir">×</button>
            </div>
            ${isExistente ? `
            <div class="cliente-acoes-edicao">
                <button class="btn-cobrar-edicao" onclick="cobrarClienteEdicao('${c.telefone}', '${c.nome.replace(/'/g, "\\'")}', '${valorFormatado}', ${c.id})" title="Enviar cobrança no WhatsApp">
                    💬 Cobrar
                </button>
                <button class="btn-recebido-edicao" onclick="marcarClienteRecebido(${c.id})" title="Marcar como recebido">
                    ✅ Recebido
                </button>
                ${c.msg_enviada == 1 ? '<span class="badge-enviado-edicao">✓ Msg enviada</span>' : ''}
            </div>
            ` : ''}
        </div>
    `}).join('');
}

// Cobrar cliente específico da edição
function cobrarClienteEdicao(telefone, nome, valor, clienteId) {
    enviarWhatsApp(telefone, nome, valor, clienteId);
}

// Marcar cliente específico como recebido (com valor editável / parcial)
async function marcarClienteRecebido(clienteId) {
    const cliente = clientesEdicao.find(c => c.id == clienteId);
    if (!cliente) return;
    
    const valorNumero = cliente.valorNumerico ? cliente.valorNumerico : parseFloat(String(cliente.valor).replace('.', '').replace(',', '.')) || 0;
    const notinhaId = notinhaIdEdicao;
    if (!notinhaId) return;
    
    const descricao = `Valor recebido do cliente "${cliente.nome}" na notinha da empresa "${document.getElementById('editar-empresa').value}". Você pode ajustar o valor se recebeu apenas uma parte.`;
    
    abrirModalRecebimento(notinhaId, valorNumero, descricao);
}

async function salvarEdicao() {
    const id = document.getElementById('editar-id').value;
    const empresa = document.getElementById('editar-empresa').value.trim();
    const dataCobranca = document.getElementById('editar-data').value;
    const clientesValidos = clientesEdicao.filter(c => c.nome.trim() !== '');

    if (!empresa || !dataCobranca || clientesValidos.length === 0) {
        showToast('Preencha todos os campos!', 'error');
        return;
    }

    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id,
                empresa,
                data_cobranca: dataCobranca,
                clientes: clientesValidos
            })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Notinha atualizada!');
            fecharEdicao();
            carregarNotinhas();
        } else {
            showToast(result.error || 'Erro', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

